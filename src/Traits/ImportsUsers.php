<?php

namespace Adldap\Laravel\Traits;

use Adldap\Models\User;
use Adldap\AdldapException;
use Illuminate\Database\Eloquent\Model;

trait ImportsUsers
{
    use AuthenticatesUsers;

    /**
     * Returns the authentication users model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract public function createModel();

    /**
     * Returns an existing or new Eloquent user from the specified Adldap user instance.
     *
     * @param User        $user
     * @param string|null $password
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModelFromAdldap(User $user, $password = null)
    {
        $model = $this->findOrCreateModelFromAdldap($user);

        // Sync the users password (if enabled). If no password is
        // given, we'll pass in a random 16 character string.
        $model = $this->syncModelPassword($model, $password ?: str_random());

        // Synchronize other active directory attributes on the model.
        $model = $this->syncModelFromAdldap($user, $model);

        // Bind the Adldap model to the eloquent model (if enabled).
        $model = ($this->getBindUserToModel() ? $this->bindAdldapToModel($user, $model) : $model);

        return $model;
    }

    /**
     * Finds an Eloquent model from the specified Adldap user.
     *
     * @param User $user
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function findOrCreateModelFromAdldap(User $user)
    {
        // Get the model key.
        $attributes = $this->getUsernameAttribute();

        // Get the model key.
        $key = key($attributes);

        // Get the username from the AD model.
        $username = $user->{$attributes[$key]};

        // Make sure we retrieve the first username result if it's an array.
        $username = (is_array($username) ? array_get($username, 0) : $username);

        // Try to find the local database user record.
        $model = $this->newEloquentQuery($key, $username)->first();

        // Create a new model instance of it isn't found.
        $model = ($model instanceof Model ? $model : $this->createModel());

        // Set the username in case of changes in active directory.
        $model->{$key} = $username;

        return $model;
    }

    /**
     * Binds the Adldap User instance to the Eloquent model instance
     * by setting its `adldapUser` public property.
     *
     * @param User  $user
     * @param Model $model
     *
     * @throws AdldapException
     *
     * @return Model
     */
    protected function bindAdldapToModel(User $user, Model $model)
    {
        $traits = class_uses_recursive(get_class($model));
        
        $trait = HasAdldapUser::class;

        if (array_key_exists($trait, $traits)) {
            // We need to verify that the User model is implementing
            // the correct trait before setting the model property.
            $model->adldapUser = $user;

            return $model;
        }

        throw new AdldapException(
            "To use the bind_user_to_model configuration option, you must 
            implement the {$trait} trait on your User model."
        );
    }

    /**
     * Fills a models attributes by the specified Users attributes.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return Model
     */
    protected function syncModelFromAdldap(User $user, Model $model)
    {
        foreach ($this->getSyncAttributes() as $modelField => $adField) {
            $value = $this->isAttributeCallback($adField) ?
                $this->handleAttributeCallback($user, $adField) :
                $this->handleAttributeRetrieval($user, $adField);

            $model->{$modelField} = $value;
        }

        return $model;
    }

    /**
     * Syncs the models password with the specified password.
     *
     * @param Model  $model
     * @param string $password
     *
     * @return Model
     */
    protected function syncModelPassword(Model $model, $password)
    {
        // If the developer doesn't want to synchronize AD passwords,
        // we'll set the password to a random 16 character string.
        $password = ($this->getPasswordSync() ? $password : str_random());

        // If the model has a set mutator for the password then
        // we'll assume that the dev is using their own
        // encryption method for passwords. Otherwise
        // we'll bcrypt it normally.
        $model->password = ($model->hasSetMutator('password') ? $password : bcrypt($password));

        return $model;
    }

    /**
     * Returns true / false if the specified string
     * is a callback for an attribute handler.
     *
     * @param string $string
     *
     * @return bool
     */
    protected function isAttributeCallback($string)
    {
        $matches = preg_grep("/(\w)@(\w)/", explode("\n", $string));

        return count($matches) > 0;
    }

    /**
     * Handles retrieving the value from an attribute callback.
     *
     * @param User   $user
     * @param string $callback
     *
     * @return mixed
     */
    protected function handleAttributeCallback(User $user, $callback)
    {
        // Explode the callback into its class and method.
        list($class, $method) = explode('@', $callback);

        // Create the handler.
        $handler = app($class);

        // Call the attribute handler method and return the result.
        return call_user_func_array([$handler, $method], [$user]);
    }

    /**
     * Handles retrieving the specified field from the User model.
     *
     * @param User   $user
     * @param string $field
     *
     * @return string|null
     */
    protected function handleAttributeRetrieval(User $user, $field)
    {
        if ($field === $this->getSchema()->thumbnail()) {
            // If the field we're retrieving is the users thumbnail photo, we need
            // to retrieve it encoded so we're able to save it to the database.
            $value = $user->getThumbnailEncoded();
        } else {
            $value = $user->{$field};

            // If the AD Value is an array, we'll
            // retrieve the first value.
            $value = (is_array($value) ? array_get($value, 0) : $value);
        }

        return $value;
    }

    /**
     * Returns a new Eloquent user query.
     *
     * @param string $key
     * @param string $username
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newEloquentQuery($key, $username)
    {
        $model = $this->createModel();

        if (method_exists($model, 'trashed')) {
            // If the trashed method exists on our User model, then we must be
            // using soft deletes. We need to make sure we include these
            // results so we don't create duplicate user records.
            $model = $model->withTrashed();
        }

        return $model->where([$key => $username]);
    }

    /**
     * Returns the configured bind user to model option for binding
     * the Adldap user model instance to the laravel model.
     *
     * @return bool
     */
    protected function getBindUserToModel()
    {
        return config('adldap_auth.bind_user_to_model', false);
    }

    /**
     * Returns the configured sync attributes for filling the
     * Laravel user model with active directory fields.
     *
     * @return array
     */
    protected function getSyncAttributes()
    {
        return config('adldap_auth.sync_attributes', ['name' => $this->getSchema()->commonName()]);
    }

    /**
     * Returns the configured password sync configuration option.
     *
     * @return bool
     */
    protected function getPasswordSync()
    {
        return config('adldap_auth.password_sync', true);
    }

    /**
     * Returns the configured option for only allowing
     * manually imported users to authenticate.
     *
     * @return mixed
     */
    protected function getOnlyAllowImportedUsers()
    {
        return config('adldap_auth.allow_only_imported', false);
    }
}

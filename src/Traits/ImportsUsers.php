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
     * Finds an Eloquent model from the specified Adldap user.
     *
     * @param User        $user
     * @param string|null $password
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function findOrCreateModelByUser(User $user, $password = null)
    {
        // Get the model key.
        $attributes = $this->getUsernameAttribute();

        // Get the model key.
        $key = key($attributes);

        // Get the username from the AD model.
        $username = $user->getFirstAttribute($attributes[$key]);

        // Try to find the local database user record.
        $model = $this->newEloquentQuery($key, $username)->first();

        // Create a new model instance of it isn't found.
        $model = ($model instanceof Model ? $model : $this->createModel());

        // Set the username in case of changes in active directory.
        $model->{$key} = $username;

        // Sync the users password (if enabled). If no password is
        // given, we'll pass in a random 16 character string.
        $this->syncModelPassword($model, $password ?: str_random());

        // Synchronize other active directory attributes on the model.
        $this->syncModelAttributes($user, $model);

        // Bind the Adldap model to the eloquent model (if enabled).
        $this->locateAndBindLdapUserToModel($model, $user);

        return $model;
    }

    /**
     * Binds the Adldap User instance to the Eloquent model instance
     * by setting its `adldapUser` public property.
     *
     * @param Model $model
     *
     * @return bool
     */
    protected function isBindingUserToModel(Model $model)
    {
        return array_key_exists(
            HasLdapUser::class,
            class_uses_recursive(get_class($model))
        );
    }

    /**
     * Retrieves the Adldap User model from the specified Laravel model.
     *
     * @param Model|null $model
     * @param User       $user
     *
     * @return void
     */
    protected function locateAndBindLdapUserToModel(Model $model = null, User $user = null)
    {
        if ($model && $this->isBindingUserToModel($model)) {
            $attributes = $this->getUsernameAttribute();

            $key = key($attributes);

            // If we have already been given a User instance,
            // we don't need to locate one using the model.
            $user = $user ?: $this->newAdldapUserQuery()
                ->where([$attributes[$key] => $model->{$key}])
                ->first();

            $model->setLdapUser($user);
        }
    }

    /**
     * Fills a models attributes by the specified Users attributes.
     *
     * @param User  $user
     * @param Model $model
     *
     * @throws AdldapException
     *
     * @return void
     */
    protected function syncModelAttributes(User $user, Model $model)
    {
        foreach ($this->getSyncAttributes() as $modelField => $adField) {
            // If the AD Field is a class, we'll assume it's an attribute handler.
            if (class_exists($adField) && $handler = app($adField)) {
                if (!method_exists($handler, 'handle')) {
                    throw new AdldapException("No handle method exists for the given handler class [$adField]");
                }

                $handler->handle($user, $model);
            } else {
                $model->{$modelField} = $this->handleAttributeRetrieval($user, $adField);
            }
        }
    }

    /**
     * Syncs the models password with the specified password.
     *
     * @param Model  $model
     * @param string $password
     *
     * @return void
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
        return $field === $this->getSchema()->thumbnail() ?
            $user->getThumbnailEncoded() : $user->getFirstAttribute($field);
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
     * Returns the configured sync attributes for filling the
     * Laravel user model with active directory fields.
     *
     * @return array
     */
    protected function getSyncAttributes()
    {
        return config('adldap_auth.sync_attributes', [
            'name' => $this->getSchema()->commonName()
        ]);
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
}

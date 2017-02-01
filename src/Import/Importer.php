<?php

namespace Adldap\Laravel\Import;

use Adldap\Models\User;
use Adldap\AdldapException;
use Illuminate\Database\Eloquent\Model;

class Importer
{
    /**
     * The LDAP user to import.
     *
     * @var User
     */
    protected $user;

    /**
     * The users password.
     *
     * @var string
     */
    protected $password;

    /**
     * Constructor.
     *
     * @param User $user
     * @param null $password
     */
    public function __construct(User $user, $password = null)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Imports the user.
     *
     * @return Model|null|static
     */
    public function run()
    {
        $attributes = $this->getUsernameAttribute();

        // Get the model key.
        $key = key($attributes);

        // Get the username from the AD model.
        $username = $this->user->getFirstAttribute($attributes[$key]);

        // Here we'll try to locate our local user record
        // for the LDAP user. If one isn't located,
        // we'll create a new one for them.
        $model = $this->findBy($key, $username) ?: $this->getModel();

        // Set the username in case of changes.
        $model->{$key} = $username;

        // Sync the users password (if enabled). If no password is
        // given, we'll pass in a random 16 character string.
        $this->syncModelPassword($model, $this->password ?: str_random());

        // Synchronize other active directory attributes on the model.
        $this->syncModelAttributes($this->user, $model);

        return $model;
    }

    /**
     * Returns a new Eloquent user query.
     *
     * @param string $key
     * @param string $username
     *
     * @return Model|null
     */
    protected function findBy($key, $username)
    {
        $model = $this->getModel();

        if (method_exists($model, 'trashed')) {
            // If the trashed method exists on our User model, then we must be
            // using soft deletes. We need to make sure we include these
            // results so we don't create duplicate user records.
            $model = $model->withTrashed();
        }

        return $model->where([$key => $username])->first();
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
                $model->{$modelField} = $this->getAttribute($user, $adField);
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
     * Retrieves the specified field from the User model.
     *
     * @param User   $user
     * @param string $field
     *
     * @return string|null
     */
    protected function getAttribute(User $user, $field)
    {
        return $field === $user->getSchema()->thumbnail() ?
            $user->getThumbnailEncoded() : $user->getFirstAttribute($field);
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
            'name' => $this->user->getSchema()->commonName(),
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

    /**
     * Returns the configured username attribute array.
     *
     * @return array
     */
    protected function getUsernameAttribute()
    {
        return config('adldap_auth.username_attribute', [
            'email' => $this->user->getSchema()->email(),
        ]);
    }

    /**
     * Returns the configured eloquent auth model.
     *
     * @return Model
     */
    protected function getModel()
    {
        return auth()->getProvider()->getModel();
    }
}

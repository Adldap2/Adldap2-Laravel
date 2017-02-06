<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\AdldapException;
use Illuminate\Database\Eloquent\Model;

class Importer implements ImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function run(User $user, Model $model, array $credentials = [])
    {
        // Here we'll try to locate our local user record
        // for the LDAP user. If one isn't located,
        // we'll create a new one for them.
        $model = $this->findByCredentials($model, $credentials) ?: $model->newInstance();

        // We'll check if we've been given a password. If one isn't
        // given we'll set it to a 16 character random string.
        $password = array_key_exists('password', $credentials) ?
            $credentials['password'] :
            str_random();

        // Sync the users password (if enabled). If no password is
        // given, we'll pass in a random 16 character string.
        $this->syncModelPassword($model, $password);

        // Synchronize other LDAP attributes on the model.
        $this->syncModelAttributes($user, $model);

        return $model;
    }

    /**
     * Retrieves an eloquent user by their credentials.
     *
     * @param Model $model
     * @param array $credentials
     *
     * @return Model|null
     */
    protected function findByCredentials(Model $model, array $credentials = [])
    {
        if (empty($credentials)) {
            return;
        }

        if (method_exists($model, 'trashed')) {
            // If the trashed method exists on our User model, then we must be
            // using soft deletes. We need to make sure we include these
            // results so we don't create duplicate user records.
            $model = $model->withTrashed();
        }

        $username = $this->getEloquentUsername();

        return $model->where([$username => $credentials[$username]])
            ->first();
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

        // If the model has a set mutator for the password then we'll
        // assume that we're using a custom encryption method for
        // passwords. Otherwise we'll bcrypt it normally.
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
            'email' => 'mail',
            'name' => 'cn',
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
     * Retrieves the eloquent users username attribute.
     *
     * @return string
     */
    protected function getEloquentUsername()
    {
        return config('adldap_auth.usernames.eloquent', 'email');
    }
}

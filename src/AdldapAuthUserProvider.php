<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Facades\Adldap;
use Adldap\Schemas\ActiveDirectory;
use Adldap\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\EloquentUserProvider;

class AdldapAuthUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     *
     * @return Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $model = parent::retrieveById($identifier);

        if ($model instanceof Authenticatable) {
            $attributes = $this->getUsernameAttribute();

            $key = key($attributes);

            $query = Adldap::users()->search();

            $query->whereEquals($attributes[$key], $model->{$key});

            $user = $query->first();

            if ($user instanceof User && $this->getBindUserToModel()) {
                $model = $this->bindAdldapToModel($user, $model);
            }
        }

        return $model;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     *
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Get the search query for users only
        $query = Adldap::users()->search();

        // Get the username input attributes
        $attributes = $this->getUsernameAttribute();

        // Get the input key
        $key = key($attributes);

        // Filter the query by the username attribute
        $query->whereEquals($attributes[$key], $credentials[$key]);

        // Retrieve the first user result
        $user = $query->first();

        // If the user is an Adldap user
        if($user instanceof User) {
            // Retrieve the users login attribute
            $username = $user->{$this->getLoginAttribute()};

            if(is_array($username)) {
                $username = Arr::get($username, 0);
            }

            // Try to log the user in
            if($this->authenticate($username, $credentials['password'])) {
                // Login was successful, we'll create a new
                // Laravel model with the Adldap user
                return $this->getModelFromAdldap($user, $credentials['password']);
            }
        }

        return null;
    }

    /**
     * Creates a local User from Active Directory
     *
     * @param User   $user
     * @param string $password
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModelFromAdldap(User $user, $password)
    {
        $email = $user->getEmail();

        $model = $this->createModel()->newQuery()->where(compact('email'))->first();

        if(!$model) {
            $model = $this->createModel();

            $model->email = $email;
            $model->password = bcrypt($password);
        }

        $model = $this->syncModelFromAdldap($user, $model);

        if($this->getBindUserToModel()) {
            $model = $this->bindAdldapToModel($user, $model);
        }

        return $model;
    }

    /**
     * Fills a models attributes by the specified Users attributes.
     *
     * @param User            $user
     * @param Authenticatable $model
     *
     * @return Authenticatable
     */
    protected function syncModelFromAdldap(User $user, Authenticatable $model)
    {
        $attributes = $this->getSyncAttributes();

        foreach($attributes as $modelField => $adField) {
            $adValue = $user->{$adField};

            if(is_array($adValue)) {
                $adValue = Arr::get($adValue, 0);
            }

            $model->{$modelField} = $adValue;
        }

        // Only save models that contain changes
        if(count($model->getDirty()) > 0) {
            $model->save();
        }

        return $model;
    }

    /**
     * Binds the Adldap User instance to the Eloquent model instance
     * by setting its `adldapUser` public property.
     *
     * @param User            $user
     * @param Authenticatable $model
     *
     * @return Authenticatable
     */
    protected function bindAdldapToModel(User $user, Authenticatable $model)
    {
        $model->adldapUser = $user;

        return $model;
    }

    /**
     * Authenticates a user against Active Directory.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function authenticate($username, $password)
    {
        return Adldap::authenticate($username, $password);
    }

    /**
     * Returns the username attribute for discovering LDAP users.
     *
     * @return array
     */
    protected function getUsernameAttribute()
    {
        return Config::get('adldap_auth.username_attribute', ['email' => ActiveDirectory::EMAIL]);
    }

    /**
     * Retrieves the Adldap login attribute for authenticating users.
     *
     * @return string
     */
    protected function getLoginAttribute()
    {
        return Config::get('adldap_auth.login_attribute', ActiveDirectory::ACCOUNT_NAME);
    }

    /**
     * Retrieves the Adldap bind user to model config option for binding
     * the Adldap user model instance to the laravel model.
     *
     * @return bool
     */
    protected function getBindUserToModel()
    {
        return Config::get('adldap_auth.bind_user_to_model', false);
    }

    /**
     * Retrieves the Adldap sync attributes for filling the
     * Laravel user model with active directory fields.
     *
     * @return array
     */
    protected function getSyncAttributes()
    {
        return Config::get('adldap_auth.sync_attributes', ['name' => ActiveDirectory::COMMON_NAME]);
    }
}

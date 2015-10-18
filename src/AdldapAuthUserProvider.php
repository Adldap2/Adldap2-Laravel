<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Facades\Adldap;
use Adldap\Schemas\ActiveDirectory;
use Adldap\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\EloquentUserProvider;

class AdldapAuthUserProvider extends EloquentUserProvider
{
    /**
     * {@inheritDoc}
     */
    public function retrieveById($identifier)
    {
        $model = parent::retrieveById($identifier);

        return $this->discoverAdldapFromModel($model);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        $model = parent::retrieveByToken($identifier, $token);

        return $this->discoverAdldapFromModel($model);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Get the search query for users only
        $query = $this->newAdldapUserQuery();

        // Get the username input attributes
        $attributes = $this->getUsernameAttribute();

        // Get the input key
        $key = key($attributes);

        // Filter the query by the username attribute
        $query->whereEquals($attributes[$key], $credentials[$key]);

        // Retrieve the first user result
        $user = $query->first();

        // If the user is an Adldap User model instance.
        if($user instanceof User) {
            // Retrieve the users login attribute.
            $username = $user->{$this->getLoginAttribute()};

            if(is_array($username)) {
                $username = Arr::get($username, 0);
            }

            // Get the password input array key.
            $key = $this->getPasswordKey();

            // Try to log the user in.
            if($this->authenticate($username, $credentials[$key])) {
                // Login was successful, we'll create a new
                // Laravel model with the Adldap user.
                return $this->getModelFromAdldap($user, $credentials[$key]);
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
        // Get the username attributes.
        $attributes = $this->getUsernameAttribute();

        // Get the model key.
        $key = key($attributes);

        // Get the username from the AD model.
        $username = $user->{$attributes[$key]};

        // Make sure we retrieve the first username
        // result if it's an array.
        if (is_array($username)) {
            $username = Arr::get($username, 0);
        }

        // Try to retrieve the model from the model key and AD username.
        $model = $this->createModel()->newQuery()->where([$key => $username])->first();

        // Create the model instance of it isn't found.
        if(!$model) $model = $this->createModel();

        // Set the username and password in case
        // of changes in active directory.
        $model->{$key} = $username;

        // Sync the users password.
        $model = $this->syncModelPassword($model, $password);

        // Synchronize other active directory
        // attributes on the model.
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
                // If the AD Value is an array, we'll
                // retrieve the first value.
                $adValue = Arr::get($adValue, 0);
            }

            $model->{$modelField} = $adValue;
        }

        // Only save models that contain changes.
        if(count($model->getDirty()) > 0) {
            $model->save();
        }

        return $model;
    }

    /**
     * Syncs the models password with the specified password.
     *
     * @param Authenticatable $model
     * @param string          $password
     *
     * @return Authenticatable
     */
    protected function syncModelPassword(Authenticatable $model, $password)
    {
        if ($model instanceof Model && $model->hasSetMutator('password')) {
            // If the model has a set mutator for the password then
            // we'll assume that the dev is using their
            // own encryption method for passwords.
            $model->password = $password;

            return $model;
        }

        // Always encrypt the model password by default.
        $model->password = bcrypt($password);

        return $model;
    }

    /**
     * Retrieves the Adldap User model from the
     * specified Laravel model.
     *
     * @param mixed $model
     *
     * @return null|Authenticatable
     */
    protected function discoverAdldapFromModel($model)
    {
        if ($model instanceof Authenticatable && $this->getBindUserToModel()) {
            $attributes = $this->getUsernameAttribute();

            $key = key($attributes);

            $query = $this->newAdldapUserQuery();

            $query->whereEquals($attributes[$key], $model->{$key});

            $user = $query->first();

            if ($user instanceof User) {
                $model = $this->bindAdldapToModel($user, $model);
            }
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
     * Returns a new Adldap user query.
     *
     * @return \Adldap\Query\Builder
     */
    protected function newAdldapUserQuery()
    {
        return Adldap::users()->search()->select($this->getSelectAttributes());
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
        return Config::get('adldap_auth.username_attribute', ['username' => ActiveDirectory::ACCOUNT_NAME]);
    }

    /**
     * Returns the password key to retrieve the
     * password from the user input array.
     *
     * @return mixed
     */
    protected function getPasswordKey()
    {
        return Config::get('adldap_auth.password_key', 'password');
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

    /**
     * Retrieves the Aldldap select attributes when performing
     * queries for authentication and binding for users.
     *
     * @return array
     */
    protected function getSelectAttributes()
    {
        return Config::get('adldap_auth.select_attributes', []);
    }
}

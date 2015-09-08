<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Facades\Adldap;
use Adldap\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\EloquentUserProvider;

class AdldapAuthUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Get the search query for users only
        $query = Adldap::users()->search();

        // Go through the credentials array and
        // scope the query by the key and
        // value besides password
        foreach($credentials as $key => $value) {
            if(! Str::contains('password', $key)) {
                $query->whereEquals($key, $value);
            }
        }

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

        $model = $this->fillModelFromAdldap($user, $model);

        $model->save();

        return $model;
    }

    /**
     * Fills a models attributes by the specified Users attributes.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return Model
     */
    protected function fillModelFromAdldap(User $user, Model $model)
    {
        $attributes = $this->getSyncAttributes();

        foreach($attributes as $modelField => $adField) {
            $adValue = $user->{$adField};

            if(is_array($adValue)) {
                $adValue = Arr::get($adValue, 0);
            }

            $model->{$modelField} = $adValue;
        }

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
     * Retrieves the Adldap login attribute for authenticating users.
     *
     * @return string
     */
    protected function getLoginAttribute()
    {
        return Config::get('adldap_auth.login_attribute', 'samaccountname');
    }

    /**
     * Retrieves the Adldap sync attributes for filling the
     * Laravel user model with active directory fields.
     *
     * @return array
     */
    protected function getSyncAttributes()
    {
        return Config::get('adldap_auth.sync_attributes', ['name' => 'cn']);
    }
}

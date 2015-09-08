<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Facades\Adldap;
use Adldap\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Auth\Authenticatable;
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
        $query = Adldap::users()->search();

        foreach($credentials as $key => $value) {
            if(! Str::contains('password', $key)) {
                $query->whereEquals($key, $value);
            }
        }

        $user = $query->first();

        if($user instanceof User) {
            $username = $user->{$this->getLoginAttribute()};

            if(is_array($username) && array_key_exists(0, $username)) {
                $username = $username[0];
            }

            if($this->authenticate($username, $credentials['password'])) {
                return $this->createModelFromAdldap($user, $credentials['password']);
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
    protected function createModelFromAdldap(User $user, $password)
    {
        $email = $user->getEmail();

        $model = $this->createModel()->newQuery()->where(compact('email'))->first();

        if(!$model) {
            $model = $this->createModel();

            $model->fill([
                'email'     => $email,
                'name'      => $user->getName(),
                'password'  => bcrypt($password),
            ]);

            $model->save();
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
}

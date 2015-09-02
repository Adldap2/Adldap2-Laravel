<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Facades\Adldap;
use Adldap\Models\User;
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
        if($this->authenticate($credentials)) {
            $user = Adldap::users()->find($credentials['email']);

            if($user instanceof User) {
                if($this->createModelFromAdlap($user, $credentials['password'])) {
                    return parent::retrieveByCredentials($credentials);
                }
            }
        }

        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  Authenticatable  $user
     * @param  array            $credentials
     *
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return $this->authenticate($credentials);
    }

    /**
     * Creates a local User from Active Directory
     *
     * @param User   $user
     * @param string $password
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function createModelFromAdlap(User $user, $password)
    {
        $email = $user->getEmail();

        $model = $this->createModel()->newQuery()->where(compact('email'))->first();

        if(!$model) {
            $model = $this->createModel();

            $model->fill([
                'email' => $email,
                'name' => $user->getName(),
                'password' => bcrypt($password),
            ]);

            $model->save();
        }

        return $model;
    }

    /**
     * Authenticates a user against Active Directory.
     *
     * @param array $credentials
     *
     * @return bool
     */
    private function authenticate(array $credentials = [])
    {
        return Adldap::authenticate($credentials['email'], $credentials['password']);
    }
}

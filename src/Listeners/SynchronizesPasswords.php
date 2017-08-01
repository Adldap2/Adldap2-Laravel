<?php

namespace Adldap\Laravel\Listeners;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Adldap\Laravel\Events\Synchronizing;

class SynchronizesPasswords
{
    /**
     * Synchronizes passwords on the model.
     *
     * @param Synchronizing $event
     */
    public function handle(Synchronizing $event)
    {
        $model = $event->model;

        if ($this->hasPasswordColumn($model)) {
            // If there's no password given with the
            // credentials, we'll use a random
            // string instead.
            $password = $this->syncing() ?
                array_get($event->credentials, 'password', Str::random()) :
                Str::random();

            // We will verify if the password needs
            // to be updated so we don't run
            // SQL updates needlessly.
            if (! Hash::check($password, $model->getAttribute($this->column()))) {
                $model->setAttribute(
                    $this->column(),
                    $model->hasSetMutator($this->column()) ? $password : bcrypt($password)
                );
            }
        }
    }

    /**
     * Determines if the password should be synchronized.
     *
     * @return bool
     */
    protected function syncing()
    {
        return Config::get('adldap_auth.passwords.sync', false);
    }

    /**
     * Retrieves the password column to use.
     *
     * @return string
     */
    protected function column()
    {
        return Config::get('adldap_auth.passwords.column', 'password');
    }

    /**
     * Determines if the database schema contains a password column.
     *
     * @param Model $model
     *
     * @return bool
     */
    protected function hasPasswordColumn(Model $model)
    {
        return Schema::hasColumn($model->getTable(), $this->column());
    }
}

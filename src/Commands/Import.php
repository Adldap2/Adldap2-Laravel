<?php

namespace Adldap\Laravel\Commands;

use Adldap\Models\User;
use Adldap\AdldapException;
use Adldap\Laravel\Events\Importing;
use Adldap\Laravel\Events\Synchronized;
use Adldap\Laravel\Events\Synchronizing;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

class Import
{
    /**
     * The LDAP user that is being imported.
     *
     * @var User
     */
    protected $user;

    /**
     * The LDAP users database model.
     *
     * @var Model
     */
    protected $model;

    /**
     * The LDAP users credentials.
     *
     * @var array
     */
    protected $credentials;

    /**
     * Constructor.
     *
     * @param User  $user
     * @param Model $model
     * @param array $credentials
     */
    public function __construct(User $user, Model $model, array $credentials = [])
    {
        $this->user = $user;
        $this->model = $model;
        $this->credentials = $credentials;
    }

    /**
     * Imports the current LDAP user.
     *
     * @return Model
     *
     * @throws AdldapException
     */
    public function handle()
    {
        // Here we'll try to locate our local user model from
        // the LDAP users model. If one isn't located,
        // we'll create a new one for them.
        $model = $this->findByCredentials() ?: $this->model->newInstance();

        if (! $model->exists) {
            Event::fire(new Importing($this->user, $model));
        }

        Event::fire(new Synchronizing($this->user, $model));

        $this->sync($model);

        Event::fire(new Synchronized($this->user, $model));

        return $model;
    }

    /**
     * Retrieves an eloquent user by their credentials.
     *
     * @return Model|null
     */
    protected function findByCredentials()
    {
        if (empty($this->credentials)) {
            return;
        }

        $query = $this->model->newQuery();

        if ($query->getMacro('withTrashed')) {
            // If the withTrashed macro exists on our User model, then we must be
            // using soft deletes. We need to make sure we include these
            // results so we don't create duplicate user records.
            $query->withTrashed();
        }

        foreach ($this->credentials as $key => $value) {
            if (! Str::contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * Fills a models attributes by the specified Users attributes.
     *
     * @param Model $model
     *
     * @throws AdldapException
     *
     * @return void
     */
    protected function sync(Model $model)
    {
        $toSync = Config::get('adldap_auth.sync_attributes', [
            'email' => 'userprincipalname',
            'name' => 'cn',
        ]);

        foreach ($toSync as $modelField => $ldapField) {
            // If the field is a loaded class, we can
            // assume it's an attribute handler.
            if (class_exists($ldapField)) {
                // We will construct the attribute handler using Laravel's
                // IoC to allow developers to utilize application
                // dependencies in the constructor.
                $handler = app($ldapField);

                if (! method_exists($handler, 'handle')) {
                    throw new AdldapException("A public 'handle()' method must be defined when using an attribute handler.");
                }

                $handler->handle($this->user, $model);
            } else {
                $model->{$modelField} = $this->user->getFirstAttribute($ldapField);
            }
        }
    }
}

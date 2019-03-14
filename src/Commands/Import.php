<?php

namespace Adldap\Laravel\Commands;

use Adldap\Models\User;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Events\Importing;
use Adldap\Laravel\Events\Synchronized;
use Adldap\Laravel\Events\Synchronizing;
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
     * Constructor.
     *
     * @param User  $user
     * @param Model $model
     */
    public function __construct(User $user, Model $model)
    {
        $this->user = $user;
        $this->model = $model;
    }

    /**
     * Imports the current LDAP user.
     *
     * @return Model
     */
    public function handle()
    {
        // Here we'll try to locate our local user model from
        // the LDAP users model. If one isn't located,
        // we'll create a new one for them.
        $model = $this->findUser() ?: $this->model->newInstance();

        if (! $model->exists) {
            Event::dispatch(new Importing($this->user, $model));
        }

        Event::dispatch(new Synchronizing($this->user, $model));

        $this->sync($model);

        Event::dispatch(new Synchronized($this->user, $model));

        return $model;
    }

    /**
     * Retrieves an eloquent user by their credentials.
     *
     * @return Model|null
     */
    protected function findUser()
    {
        $query = $this->model->newQuery();

        if ($query->getMacro('withTrashed')) {
            // If the withTrashed macro exists on our User model, then we must be
            // using soft deletes. We need to make sure we include these
            // results so we don't create duplicate user records.
            $query->withTrashed();
        }

        // We'll try to locate the user by their object guid,
        // otherwise we'll locate them by their username.
        return $query->where(
            Resolver::getDatabaseIdColumn(),
            '=',
            $this->user->getConvertedGuid()
        )->orWhere(
            Resolver::getDatabaseUsernameColumn(),
            '=',
            $this->user->getFirstAttribute(Resolver::getLdapDiscoveryAttribute())
        )->first();
    }

    /**
     * Fills a models attributes by the specified Users attributes.
     *
     * @param Model $model
     *
     * @return void
     */
    protected function sync(Model $model)
    {
        // Set the users LDAP identifier.
        $model->setAttribute(
            Resolver::getDatabaseIdColumn(), $this->user->getConvertedGuid()
        );

        foreach ($this->getLdapSyncAttributes() as $modelField => $ldapField) {
            // If the field is a loaded class and contains a `handle()` method,
            // we need to construct the attribute handler.
            if ($this->isHandler($ldapField)) {
                // We will construct the attribute handler using Laravel's
                // IoC to allow developers to utilize application
                // dependencies in the constructor.
                /** @var mixed $handler */
                $handler = app($ldapField);

                $handler->handle($this->user, $model);
            } else {
                // We'll try to retrieve the value from the LDAP model. If the LDAP field is a string,
                // we'll assume the developer wants the attribute, or a null value. Otherwise,
                // the raw value of the LDAP field will be used.
                $model->{$modelField} = is_string($ldapField) ? $this->user->getFirstAttribute($ldapField) : $ldapField;
            }
        }
    }

    /**
     * Determines if the given handler value is a class that contains the 'handle' method.
     *
     * @param mixed $handler
     *
     * @return bool
     */
    protected function isHandler($handler)
    {
        return is_string($handler) && class_exists($handler) && method_exists($handler, 'handle');
    }

    /**
     * Returns the configured LDAP sync attributes.
     *
     * @return array
     */
    protected function getLdapSyncAttributes()
    {
        return Config::get('ldap_auth.sync_attributes', [
            'email' => 'userprincipalname',
            'name' => 'cn',
        ]);
    }
}

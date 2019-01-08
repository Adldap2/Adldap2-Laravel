<?php

namespace Adldap\Laravel\Commands;

use Adldap\Models\User;
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
        $this->user = $this->transformUsername($user);
        $this->model = $model;
        $this->credentials = $credentials;
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
                // We need to lowercase all values so we locate the
                // proper model. This avoids case sensitivity.
                $query->where($key, strtolower($value));
            }
        }

        return $query->first();
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
     * Transforms the username of the given user to avoid case sensitivity issues.
     *
     * We want to transform the username on the user model so it persists through attribute handlers.
     *
     * @param User $user
     *
     * @return User
     */
    protected function transformUsername(User $user)
    {
        $attribute = $this->getLdapDiscoveryUsername();

        $user->setFirstAttribute($attribute, strtolower($user->getFirstAttribute($attribute)));

        return $user;
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

    /**
     * Returns the configured LDAP discovery username attribute.
     *
     * @return string
     */
    protected function getLdapDiscoveryUsername()
    {
        return Config::get('ldap_auth.usernames.ldap.discover', 'userprincipalname');
    }
}

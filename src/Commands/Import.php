<?php

namespace Adldap\Laravel\Commands;

use Adldap\Models\User;
use Adldap\AdldapException;
use Adldap\Laravel\Events\Importing;
use Adldap\Laravel\Events\Synchronizing;
use Adldap\Laravel\Events\Synchronized;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
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
     */
    public function handle()
    {
        // Here we'll try to locate our local user record
        // for the LDAP user. If one isn't located,
        // we'll create a new one for them.
        $model = $this->findByCredentials() ?: $this->model->newInstance();

        if (! $model->exists) {
            Event::fire(new Importing($this->user, $model));
        }

        Event::fire(new Synchronizing($this->user, $model));

        // Synchronize LDAP attributes on the model.
        $this->syncModelAttributes($model);

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
    protected function syncModelAttributes(Model $model)
    {
        foreach ($this->getSyncAttributes() as $modelField => $ldapField) {
            if ($handler = $this->getHandler($ldapField)) {
                $handler->handle($this->user, $model);
            } else {
                $model->{$modelField} = $this->getAttribute($this->user, $ldapField);
            }
        }
    }

    /**
     * Constructs the given handler if it exists and contains a `handle` method.
     *
     * @param string $handler
     *
     * @return mixed|null
     *
     * @throws AdldapException
     */
    protected function getHandler($handler)
    {
        if (class_exists($handler) && $handler = app($handler)) {
            if (!method_exists($handler, 'handle')) {
                $name = get_class($handler);

                throw new AdldapException("No handle method exists for the given handler: $name");
            }

            return $handler;
        }
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
            'email' => 'userprincipalname',
            'name' => 'cn',
        ]);
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

<?php

namespace Adldap\Laravel\Commands;

use Adldap\Laravel\Facades\Resolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class UserImportScope implements Scope
{
    /**
     * The LDAP users object guid.
     *
     * @var string
     */
    protected $guid;

    /**
     * The LDAP users username.
     *
     * @var string
     */
    protected $username;

    /**
     * Constructor.
     *
     * @param string $guid
     * @param string $username
     */
    public function __construct($guid, $username)
    {
        $this->guid = $guid;
        $this->username = $username;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $query
     * @param Model   $model
     *
     * @return void
     */
    public function apply(Builder $query, Model $model)
    {
        $this->user($query);
    }

    /**
     * Applies the user scope to the given Eloquent query builder.
     *
     * @param Builder $query
     */
    protected function user(Builder $query)
    {
        // We'll try to locate the user by their object guid,
        // otherwise we'll locate them by their username.
        $query
            ->where(Resolver::getDatabaseIdColumn(), '=', $this->getGuid())
            ->orWhere(Resolver::getDatabaseUsernameColumn(), '=', $this->getUsername());
    }

    /**
     * Returns the LDAP users object guid.
     *
     * @return string
     */
    protected function getGuid()
    {
        return $this->guid;
    }

    /**
     * Returns the LDAP users username.
     *
     * @return string
     */
    protected function getUsername()
    {
        return $this->username;
    }
}

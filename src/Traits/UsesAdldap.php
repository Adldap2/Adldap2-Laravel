<?php

namespace Adldap\Laravel\Traits;

use Adldap\Query\Builder;
use Adldap\Laravel\Facades\Adldap;

trait UsesAdldap
{
    /**
     * Returns a new Adldap user query.
     *
     * @param string|null $provider
     *
     * @return Builder
     */
    protected function newAdldapUserQuery($provider = null)
    {
        $query = $this->getAdldap($provider)->search()->users();

        $this->applyScopes($query);

        return $query;
    }

    /**
     * Applies the configured scopes to the given query.
     *
     * @param Builder $query
     */
    protected function applyScopes(Builder $query)
    {
        foreach ($this->getScopes() as $scope) {
            $scope = app($scope);

            $scope->apply($query);
        }
    }

    /**
     * Returns Adldap's current attribute schema.
     *
     * @return \Adldap\Schemas\SchemaInterface
     */
    protected function getSchema()
    {
        return $this->getAdldap()->getSchema();
    }

    /**
     * Returns the root Adldap provider instance.
     *
     * @param string $provider
     *
     * @return \Adldap\Connections\ProviderInterface
     */
    protected function getAdldap($provider = null)
    {
        $provider = $provider ?: $this->getDefaultConnectionName();

        return Adldap::getProvider($provider);
    }

    /**
     * Returns the configured query scopes.
     *
     * @return array
     */
    protected function getScopes()
    {
        return config('adldap_auth.scopes', []);
    }

    /**
     * Returns the configured default connection name.
     *
     * @return mixed
     */
    protected function getDefaultConnectionName()
    {
        return config('adldap_auth.connection', 'default');
    }
}

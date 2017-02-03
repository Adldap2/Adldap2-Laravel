<?php

namespace Adldap\Laravel\Scopes;

use Adldap\Query\Builder;

interface ScopeInterface
{
    /**
     * Apply the scope to a given Adldap query builder.
     *
     * @param Builder $builder
     *
     * @return void
     */
    public function apply(Builder $builder);
}

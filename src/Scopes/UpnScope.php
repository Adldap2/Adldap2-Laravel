<?php

namespace Adldap\Laravel\Scopes;

use Adldap\Query\Builder;

class UpnScope implements ScopeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $builder)
    {
        $builder->whereHas($builder->getSchema()->userPrincipalName());
    }
}

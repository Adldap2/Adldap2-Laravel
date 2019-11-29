<?php

namespace Adldap\Laravel\Tests\Scopes;

use Adldap\Laravel\Scopes\ScopeInterface;
use Adldap\Query\Builder;

class JohnDoeScope implements ScopeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $builder)
    {
        $builder->whereCn('John Doe');
    }
}

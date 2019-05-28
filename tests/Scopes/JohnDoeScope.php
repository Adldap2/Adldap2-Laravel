<?php

namespace Adldap\Laravel\Tests\Scopes;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

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

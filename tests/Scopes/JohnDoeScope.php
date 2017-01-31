<?php

namespace Adldap\Laravel\Tests\Scopes;

use Adldap\Laravel\Scopes\Scope;
use Adldap\Query\Builder;

class JohnDoeScope implements Scope
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $builder)
    {
        $builder->whereCn('John Doe');
    }
}

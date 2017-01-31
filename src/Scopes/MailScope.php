<?php

namespace Adldap\Laravel\Scopes;

use Adldap\Query\Builder;

class MailScope implements Scope
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $builder)
    {
        $builder->whereHas($builder->getSchema()->email());
    }
}

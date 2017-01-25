<?php

namespace Adldap\Laravel\Tests\Handlers;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class LdapAttributeHandler
{
    /**
     * Synchronizes ldap attributes to the specified model.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return void
     */
    public function handle(User $user, Model $model)
    {
        $model->name = 'handled';
    }
}

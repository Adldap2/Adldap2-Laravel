<?php

namespace Adldap\Laravel\Tests\Handlers;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class LdapAttributeHandler
{
    /**
     * Returns the common name of the AD User.
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

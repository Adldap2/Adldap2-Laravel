<?php

namespace Adldap\Laravel\Traits;

trait HasAdldapUser
{
    /**
     * The Adldap User that is bound to the current model.
     *
     * @var null|\Adldap\Models\User
     */
    public $adldapUser;
}

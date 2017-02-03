<?php

namespace Adldap\Laravel\Import;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

interface ImporterInterface
{
    /**
     * Imports the user.
     *
     * @param User  $user
     * @param Model $model
     * @param array $credentials
     *
     * @return Model|null
     */
    public function run(User $user, Model $model, array $credentials = []);
}

<?php

namespace Adldap\Laravel\Validation\Rules;

use Adldap\Laravel\Events\AuthenticatedModelTrashed;

class DenyTrashed extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        if (method_exists($this->model, 'trashed') && $this->model->trashed()) {
            event(new AuthenticatedModelTrashed($this->user, $this->model));

            return false;
        }

        return true;
    }
}

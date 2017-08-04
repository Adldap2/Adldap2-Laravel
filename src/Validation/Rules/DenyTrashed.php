<?php

namespace Adldap\Laravel\Validation\Rules;

use Illuminate\Support\Facades\Event;
use Adldap\Laravel\Events\AuthenticatedModelTrashed;

class DenyTrashed extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        if ($this->isTrashed()) {
            Event::fire(
                new AuthenticatedModelTrashed($this->user, $this->model)
            );

            return false;
        }

        return true;
    }

    /**
     * Determines if the current model is trashed.
     *
     * @return bool
     */
    protected function isTrashed()
    {
        return method_exists($this->model, 'trashed') && $this->model->trashed();
    }
}

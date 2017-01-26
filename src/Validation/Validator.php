<?php

namespace Adldap\Laravel\Validation;

use Adldap\Laravel\Validation\Rules\Rule;

class Validator
{
    /**
     * The validation rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Constructor.
     *
     * @param array $rules
     */
    public function __construct(array $rules = [])
    {
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Determine if the data passes validation.
     *
     * @return bool
     */
    public function passes()
    {
        foreach ($this->rules as $rule) {
            if (! $rule->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the data fails validation.
     *
     * @return bool
     */
    public function fails()
    {
        return ! $this->passes();
    }

    /**
     * Adds a rule to the validator.
     *
     * @param Rule $rule
     */
    public function addRule(Rule $rule)
    {
        $this->rules[] = $rule;
    }
}

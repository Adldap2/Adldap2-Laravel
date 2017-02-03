<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\Laravel\Traits\UsesAdldap;
use Adldap\Laravel\Validation\Validator;
use Adldap\Laravel\Import\Importer;
use Adldap\Laravel\Import\ImporterInterface;
use Adldap\Laravel\Resolvers\UserResolver;
use Adldap\Laravel\Resolvers\ResolverInterface;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class Provider implements UserProvider
{
    use UsesAdldap;

    /**
     * The user resolver.
     *
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * The user importer.
     *
     * @var ImporterInterface
     */
    protected $importer;

    /**
     * Returns a new user resolver.
     *
     * @return ResolverInterface
     */
    protected function resolver()
    {
        $resolver = $this->getResolver();

        return $this->resolver ?: $this->resolver = new $resolver($this->provider());
    }

    /**
     * Returns a new importer.
     *
     * @return ImporterInterface
     */
    protected function importer()
    {
        $importer = $this->getImporter();

        return $this->importer ?: $this->importer = new $importer();
    }

    /**
     * Returns a new authentication validator.
     *
     * @param array $rules
     *
     * @return Validator
     */
    protected function validator(array $rules = [])
    {
        return new Validator($rules);
    }

    /**
     * Returns an array of constructed rules.
     *
     * @param User            $user
     * @param Authenticatable $model
     *
     * @return array
     */
    protected function rules(User $user, Authenticatable $model)
    {
        $rules = [];

        foreach (config('adldap_auth.rules', []) as $rule) {
            $rules[] = new $rule($user, $model);
        }

        return $rules;
    }

    /**
     * Returns the configured importer.
     *
     * @return string
     */
    protected function getImporter()
    {
        return config('adldap_auth.importer', Importer::class);
    }

    /**
     * Returns the configured user resolver.
     *
     * @return string
     */
    public function getResolver()
    {
        return config('adldap_auth.resolver', UserResolver::class);
    }
}

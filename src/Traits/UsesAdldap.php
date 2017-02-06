<?php

namespace Adldap\Laravel\Traits;

use Adldap\Models\User;
use Adldap\Laravel\Auth\Resolver;
use Adldap\Laravel\Auth\Importer;
use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\Validation\Validator;
use Adldap\Laravel\Auth\ImporterInterface;
use Adldap\Laravel\Auth\ResolverInterface;
use Illuminate\Contracts\Auth\Authenticatable;

trait UsesAdldap
{
    /**
     * The user resolver.
     *
     * @var \Adldap\Laravel\Auth\ResolverInterface
     */
    protected $resolver;

    /**
     * The user importer.
     *
     * @var \Adldap\Laravel\Auth\ImporterInterface
     */
    protected $importer;

    /**
     * Sets the current resolver.
     *
     * @param ResolverInterface $resolver
     */
    public function setResolver(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Sets the current importer.
     *
     * @param ImporterInterface $importer
     */
    public function setImporter(ImporterInterface $importer)
    {
        $this->importer = $importer;
    }

    /**
     * Returns the configured user resolver.
     *
     * @return \Adldap\Laravel\Auth\ResolverInterface
     */
    public function getResolver()
    {
        $resolver = config('adldap_auth.resolver', Resolver::class);

        return $this->resolver ?: $this->resolver = new $resolver($this->getLdapProvider());
    }

    /**
     * Returns a new importer.
     *
     * @return \Adldap\Laravel\Auth\ImporterInterface
     */
    public function getImporter()
    {
        $importer = config('adldap_auth.importer', Importer::class);

        return $this->importer ?: $this->importer = new $importer();
    }

    /**
     * Retrieves a connection provider from the Adldap instance.
     *
     * @param string|null $provider
     *
     * @return \Adldap\Connections\ProviderInterface
     */
    public function getLdapProvider($provider = null)
    {
        $provider = $provider ?: $this->getDefaultConnectionName();

        return Adldap::getProvider($provider);
    }

    /**
     * Returns the configured default connection name.
     *
     * @return mixed
     */
    public function getDefaultConnectionName()
    {
        return config('adldap_auth.connection', 'default');
    }

    /**
     * Returns an array of constructed rules.
     *
     * @param User                 $user
     * @param Authenticatable|null $model
     *
     * @return array
     */
    protected function getRules(User $user, Authenticatable $model = null)
    {
        $rules = [];

        foreach (config('adldap_auth.rules', []) as $rule) {
            $rules[] = new $rule($user, $model);
        }

        return $rules;
    }

    /**
     * Returns a new authentication validator.
     *
     * @param array $rules
     *
     * @return Validator
     */
    protected function newValidator(array $rules = [])
    {
        return new Validator($rules);
    }
}

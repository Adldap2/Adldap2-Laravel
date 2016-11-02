<?php

namespace Adldap\Laravel;

use InvalidArgumentException;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Laravel\Commands\Import;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Hashing\Hasher;

class AdldapAuthServiceProvider extends ServiceProvider
{
    /**
     * Run service provider boot operations.
     *
     * @return void
     */
    public function boot()
    {
        $config = __DIR__.'/Config/auth.php';

        // Add publishable configuration.
        $this->publishes([
            $config => config_path('adldap_auth.php'),
        ], 'adldap');

        $this->mergeConfigFrom($config, 'adldap_auth');

        $auth = $this->getAuth();

        if (method_exists($auth, 'provider')) {
            // If the provider method exists, we're running Laravel 5.2.
            // Register the adldap auth user provider.
            $auth->provider('adldap', function ($app, array $config) {
                return $this->newAdldapAuthUserProvider($app['hash'], $config);
            });
        } else {
            // Otherwise we're using 5.0 || 5.1
            // Extend Laravel authentication with Adldap driver.
            $auth->extend('adldap', function ($app) {
                return $this->newAdldapAuthUserProvider($app['hash'], $app['config']['auth.model']);
            });
        }

        $this->commands([Import::class]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['auth'];
    }

    /**
     * Returns a new Adldap user provider.
     *
     * @param Hasher $hasher
     * @param array  $config
     *
     * @return \Illuminate\Contracts\Auth\UserProvider
     *
     * @throws InvalidArgumentException
     */
    protected function newAdldapAuthUserProvider(Hasher $hasher, array $config)
    {
        $provider = $this->getAdldapUserProvider();

        // We need to verify if the provider we've been given is supported.
        switch ($provider) {
            case DatabaseUserProvider::class:
                return new $provider($hasher, $config['model']);
            case NoDatabaseUserProvider::class:
                return new $provider;
        }

        throw new InvalidArgumentException(
            "The given Adldap provider {$provider} is not supported or does not exist."
        );
    }

    /**
     * Returns the root Auth instance.
     *
     * @return mixed
     */
    protected function getAuth()
    {
        return Auth::getFacadeRoot();
    }

    /**
     * Returns the configured user provider.
     *
     * @return string
     */
    protected function getAdldapUserProvider()
    {
        return config('adldap_auth.provider', DatabaseUserProvider::class);
    }
}

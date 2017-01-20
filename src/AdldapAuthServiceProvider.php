<?php

namespace Adldap\Laravel;

use InvalidArgumentException;
use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
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

        $auth = Auth::getFacadeRoot();

        if (method_exists($auth, 'provider')) {
            // If the provider method exists, we're running Laravel 5.2.
            // Register the adldap auth user provider.
            $auth->provider('adldap', function ($app, array $config) {
                return $this->newUserProvider($app['hash'], $config);
            });
        } else {
            // Otherwise we're using 5.0 || 5.1
            // Extend Laravel authentication with Adldap driver.
            $auth->extend('adldap', function ($app) {
                return $this->newUserProvider($app['hash'], $app['config']['auth']);
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
    protected function newUserProvider(Hasher $hasher, array $config)
    {
        $provider = $this->getUserProvider();

        switch ($provider) {
            case DatabaseUserProvider::class:
                if (array_key_exists('model', $config)) {
                    return new $provider($hasher, $config['model']);
                }

                throw new InvalidArgumentException(
                    "No model is configured. You must configure a model to use with the [{$provider}]."
                );
            case NoDatabaseUserProvider::class:
                return new $provider;
        }

        throw new InvalidArgumentException(
            "The configured Adldap provider [{$provider}] is not supported or does not exist."
        );
    }

    /**
     * Returns the configured user provider.
     *
     * @return string
     */
    protected function getUserProvider()
    {
        return config('adldap_auth.provider', DatabaseUserProvider::class);
    }
}

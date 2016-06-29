<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Commands\Import;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AdldapAuthServiceProvider extends ServiceProvider
{
    /**
     * Run service provider boot operations.
     *
     * @return void
     */
    public function boot()
    {
        $auth = __DIR__.'/Config/auth.php';

        // Add publishable configuration.
        $this->publishes([
            $auth => config_path('adldap_auth.php'),
        ], 'adldap');

        $this->mergeConfigFrom($auth, 'adldap_auth');

        $auth = $this->getAuth();

        if (method_exists($auth, 'provider')) {
            // If the provider method exists, we're running Laravel 5.2.
            // Register the adldap auth user provider.
            $auth->provider('adldap', function ($app, array $config) {
                return $this->newAdldapAuthUserProvider($app['hash'], $config['model']);
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
     * Returns a new instance of the AdldapAuthUserProvider.
     *
     * @param Hasher $hasher
     * @param string $model
     *
     * @return AdldapAuthUserProvider
     */
    protected function newAdldapAuthUserProvider(Hasher $hasher, $model)
    {
        return new AdldapAuthUserProvider($hasher, $model);
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
}

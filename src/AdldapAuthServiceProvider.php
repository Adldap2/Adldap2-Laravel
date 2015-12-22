<?php

namespace Adldap\Laravel;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AdldapAuthServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

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

        // Register the adldap auth user provider.
        Auth::provider('adldap', function ($app, array $config) {
            return new AdldapAuthUserProvider($app['hash'], $config['model']);
        });
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
}

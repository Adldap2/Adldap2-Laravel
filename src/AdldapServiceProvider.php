<?php

namespace Adldap\Laravel;

use Adldap\Adldap;
use Adldap\Laravel\Exceptions\ConfigurationMissingException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AdldapServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Run service provider boot operations.
     */
    public function boot()
    {
        $config = __DIR__.'/Config/config.php';

        $this->publishes([
            $config => config_path('adldap.php'),
        ], 'adldap');

        $this->mergeConfigFrom($config, 'adldap');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Bind the Adldap instance to the IoC
        $this->app->bind('adldap', function (Application $app) {
            $config = $app->make('config');

            $settings = $config->get('adldap');

            // Verify configuration.
            if (is_null($settings)) {
                $message = 'Adldap configuration could not be found. Try re-publishing using `php artisan vendor:publish --tag="adldap"`.';

                throw new ConfigurationMissingException($message);
            }

            // Create a new Adldap instance.
            return new Adldap($settings['connection_settings'], new $settings['connection'](), $settings['auto_connect']);
        });

        // Bind the Adldap contract to the Adldap object
        // in the IoC for dependency injection.
        $this->app->bind('Adldap\Contracts\Adldap', 'adldap');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['adldap'];
    }
}

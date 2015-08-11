<?php

namespace Adldap\Laravel;

use Adldap\Adldap;
use Adldap\Laravel\Exceptions\ConfigurationMissingException;
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
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('adldap.php'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $config = $this->app['config']->get('adldap');

        // Bind the Adldap instance to the IoC
        $this->app->bind('adldap', function() use ($config)
        {
            // Verify configuration
            if(is_null($config)) {
                $message = 'Adldap configuration could not be found. Try re-publishing using `php artisan vendor:publish`.';

                throw new ConfigurationMissingException($message);
            }

            return new Adldap($config['connection_settings'], new $config['connection'], $config['auto_connect']);
        });
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

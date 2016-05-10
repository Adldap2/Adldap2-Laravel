<?php

namespace Adldap\Laravel;

use Adldap\Adldap;
use Adldap\Connections\Provider;
use Adldap\Contracts\AdldapInterface;
use Adldap\Laravel\Exceptions\ConfigurationMissingException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AdldapServiceProvider extends ServiceProvider
{
    /**
     * Run service provider boot operations.
     *
     * @return void
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
     *
     * @return void
     */
    public function register()
    {
        // Bind the Adldap instance to the IoC
        $this->app->singleton('adldap', function (Application $app) {
            $config = $app->make('config')->get('adldap');

            // Verify configuration exists.
            if (is_null($config)) {
                $message = 'Adldap configuration could not be found. Try re-publishing using `php artisan vendor:publish --tag="adldap"`.';

                throw new ConfigurationMissingException($message);
            }

            return $this->addProviders(new Adldap(), $config['connections']);
        });

        // Bind the Adldap contract to the Adldap object
        // in the IoC for dependency injection.
        $this->app->singleton(AdldapInterface::class, 'adldap');
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

    /**
     * Adds providers to the specified Adldap instance.
     *
     * @param Adldap $adldap
     * @param array  $connections
     *
     * @return Adldap
     * @throws \Adldap\Exceptions\ConnectionException
     */
    protected function addProviders(Adldap $adldap, array $connections = [])
    {
        // Go through each connection and construct a Provider.
        foreach ($connections as $name => $settings) {
            $connection = new $settings['connection']();
            $schema = new $settings['schema']();

            // Construct a new connection Provider with its settings.
            $provider = new Provider($settings['connection_settings'], $connection, $schema);

            if ($settings['auto_connect'] === true) {
                // Try connecting to the provider if `auto_connect` is true.
                $provider->connect();
            }

            $adldap->addProvider($name, $provider);
        }

        return $adldap;
    }
}

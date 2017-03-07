<?php

namespace Adldap\Laravel;

use Adldap\Adldap;
use Adldap\AdldapInterface;
use Adldap\Connections\Provider;
use Adldap\Schemas\SchemaInterface;
use Adldap\Connections\ConnectionInterface;
use Adldap\Laravel\Exceptions\ConfigurationMissingException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Container\Container;

class AdldapServiceProvider extends ServiceProvider
{
    /**
     * Run service provider boot operations.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isLumen()) {
            return;
        }

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
        $this->app->singleton('adldap', function (Container $app) {
            $config = $app->make('config')->get('adldap');

            // Verify configuration exists.
            if (is_null($config)) {
                $message = 'Adldap configuration could not be found. Try re-publishing using `php artisan vendor:publish --tag="adldap"`.';

                throw new ConfigurationMissingException($message);
            }

            return $this->addProviders($this->newAdldap(), $config['connections']);
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
     * @throws \Adldap\Auth\BindException
     *
     * @return Adldap
     */
    protected function addProviders(Adldap $adldap, array $connections = [])
    {
        // Go through each connection and construct a Provider.
        collect($connections)->each(function ($settings, $name) use ($adldap) {
            //fix error port integer
            $settings['connection_settings']['port'] = intval($settings['connection_settings']['port']) ;
            // Create a new provider.
            $provider = $this->newProvider(
                $settings['connection_settings'],
                new $settings['connection'](),
                new $settings['schema']()
            );

            // Try connecting to the provider if `auto_connect` is true.
            if (isset($settings['auto_connect']) && $settings['auto_connect'] === true) {
                $provider->connect();
            }

            // Add the provider to the Adldap container.
            $adldap->addProvider($provider, $name);
        });

        return $adldap;
    }

    /**
     * Returns a new Adldap instance.
     *
     * @return Adldap
     */
    protected function newAdldap()
    {
        return new Adldap();
    }

    /**
     * Returns a new Provider instance.
     *
     * @param array                    $configuration
     * @param ConnectionInterface|null $connection
     * @param SchemaInterface          $schema
     *
     * @return Provider
     */
    protected function newProvider($configuration = [], ConnectionInterface $connection = null, SchemaInterface $schema = null)
    {
        return new Provider($configuration, $connection, $schema);
    }

    /**
     * Determines if the current application is Lumen.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen');
    }
}

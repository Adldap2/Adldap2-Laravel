<?php

namespace Adldap\Laravel;

use Adldap\Adldap;
use Adldap\Connections\Configuration;
use Adldap\Connections\Manager;
use Adldap\Connections\Provider;
use Adldap\Contracts\AdldapInterface;
use Adldap\Laravel\Exceptions\ConfigurationMissingException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AdldapServiceProvider extends ServiceProvider
{
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
        $this->app->singleton('adldap', function (Application $app) {
            $config = $app->make('config')->get('adldap');

            // Verify configuration exists.
            if (is_null($config)) {
                $message = 'Adldap configuration could not be found. Try re-publishing using `php artisan vendor:publish --tag="adldap"`.';

                throw new ConfigurationMissingException($message);
            }

            // Create a new connection Manager.
            $manager = new Manager();

            // Retrieve the LDAP connections.
            $connections = $config['connections'];

            // Go through each connection and construct a Provider.
            foreach ($connections as $name => $settings) {
                $configuration = new Configuration($settings['connection_settings']);
                $connection = new $settings['connection']();
                $schema = new $settings['schema']();

                // Construct a new connection Provider with its settings.
                $provider = new Provider($configuration, $connection, $schema);

                if ($settings['auto_connect'] === true) {
                    // Try connecting to the provider if `auto_connect` is true.
                    $provider->connect();
                }

                // Add the Provider to the Manager.
                $manager->add($name, $provider);
            }

            return new Adldap($manager);
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
}

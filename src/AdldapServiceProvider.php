<?php

namespace Adldap\Laravel;

use Adldap\Adldap;
use Adldap\AdldapException;
use Adldap\AdldapInterface;
use Illuminate\Support\Str;
use Adldap\Connections\Provider;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Adldap\Connections\ConnectionInterface;

class AdldapServiceProvider extends ServiceProvider
{
    /**
     * We'll defer loading this service provider so our
     * LDAP connection isn't instantiated unless
     * requested to speed up our application.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Run service provider boot operations.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isLogging()) {
            Adldap::setLogger(logger());
        }

        if ($this->isLumen()) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $config = __DIR__.'/Config/config.php';

            $this->publishes([
                $config => config_path('ldap.php'),
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind the Adldap contract to the Adldap object
        // in the IoC for dependency injection.
        $this->app->singleton(AdldapInterface::class, function (Container $app) {
            $config = $app->make('config')->get('ldap');

            // Verify configuration exists.
            if (is_null($config)) {
                $message = 'Adldap configuration could not be found. Try re-publishing using `php artisan vendor:publish`.';

                throw new \RuntimeException($message);
            }

            return $this->addProviders($this->newAdldap(), $config['connections']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [AdldapInterface::class];
    }

    /**
     * Adds providers to the specified Adldap instance.
     *
     * If a provider is configured to auto connect,
     * this method will throw a BindException.
     *
     * @param Adldap $ldap
     * @param array  $connections
     *
     * @return Adldap
     */
    protected function addProviders(AdldapInterface $ldap, array $connections = [])
    {
        // Go through each connection and construct a Provider.
        foreach ($connections as $name => $config) {
            // Create a new connection with its configured name.
            $connection = new $config['connection']($name);

            // Create a new provider.
            $provider = $this->newProvider(
                $config['settings'],
                $connection
            );

            // If auto connect is enabled, an attempt will be made to bind to
            // the LDAP server with the configured credentials. If this
            // fails then the exception will be logged (if enabled).
            if ($this->shouldAutoConnect($config)) {
                try {
                    $provider->connect();
                } catch (AdldapException $e) {
                    if ($this->isLogging()) {
                        logger()->error($e);
                    }
                }
            }

            // Add the provider to the LDAP container.
            $ldap->addProvider($provider, $name);
        }

        return $ldap;
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
     * Returns a new LDAP Provider instance.
     *
     * @param array                    $configuration
     * @param ConnectionInterface|null $connection
     *
     * @return Provider
     */
    protected function newProvider($configuration = [], ConnectionInterface $connection = null)
    {
        return new Provider($configuration, $connection);
    }

    /**
     * Determines if the given settings has auto connect enabled.
     *
     * @param array $settings
     *
     * @return bool
     */
    protected function shouldAutoConnect(array $settings)
    {
        return array_key_exists('auto_connect', $settings)
            && $settings['auto_connect'] === true;
    }

    /**
     * Determines whether logging is enabled.
     *
     * @return bool
     */
    protected function isLogging()
    {
        return Config::get('ldap.logging', false);
    }

    /**
     * Determines if the current application is a Lumen instance.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return Str::contains($this->app->version(), 'Lumen');
    }
}

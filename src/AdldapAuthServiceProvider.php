<?php

namespace Adldap\Laravel;

use InvalidArgumentException;
use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\Resolvers\UserResolver;
use Adldap\Laravel\Resolvers\ResolverInterface;
use Adldap\Laravel\Commands\Console\Import;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Laravel\Listeners\BindsLdapUserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\Events\Authenticated;

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
            // If the provider method exists, we're running Laravel >= 5.2
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

        $this->commands(Import::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBindings();

        $this->registerListeners();
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
     * Registers the application bindings.
     *
     * @return void
     */
    protected function registerBindings()
    {
        $this->app->bind(ResolverInterface::class, function () {
            return $this->newUserResolver();
        });
    }

    /**
     * Registers the event listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        Event::listen(Authenticated::class, BindsLdapUserModel::class);
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
        $provider = $this->userProvider();

        switch ($provider) {
            case DatabaseUserProvider::class:
                if (array_key_exists('model', $config)) {
                    return new $provider($hasher, $config['model']);
                }

                throw new InvalidArgumentException(
                    "No model is configured. You must configure a model to use with the {$provider}."
                );
            case NoDatabaseUserProvider::class:
                return new $provider;
        }

        throw new InvalidArgumentException(
            "The configured Adldap provider [{$provider}] is not supported or does not exist."
        );
    }

    /**
     * Returns a new user resolver.
     *
     * @return ResolverInterface
     */
    protected function newUserResolver()
    {
        return new UserResolver($this->ldapProvider());
    }

    /**
     * Retrieves a connection provider from the Adldap instance.
     *
     * @return \Adldap\Connections\ProviderInterface
     */
    protected function ldapProvider()
    {
        return Adldap::getProvider($this->connection());
    }

    /**
     * Returns the configured user provider class.
     *
     * @return string
     */
    protected function userProvider()
    {
        return Config::get('adldap_auth.provider', DatabaseUserProvider::class);
    }

    /**
     * Returns the configured default connection name.
     *
     * @return string
     */
    public function connection()
    {
        return Config::get('adldap_auth.connection', 'default');
    }
}

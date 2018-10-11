# Events

Adldap2-Laravel raises a variety of events throughout authentication attempts.

You may attach listeners to these events in your `EventServiceProvider`:

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [

    'Adldap\Laravel\Events\Authenticating' => [
        'App\Listeners\LogAuthenticating',
    ],

    'Adldap\Laravel\Events\Authenticated' => [
        'App\Listeners\LogLdapAuthSuccessful',
    ],
    
    'Adldap\Laravel\Events\AuthenticationSuccessful' => [
        'App\Listeners\LogAuthSuccessful'
    ],
    
    'Adldap\Laravel\Events\AuthenticationFailed' => [
        'App\Listeners\LogAuthFailure',
    ],
    
    'Adldap\Laravel\Events\AuthenticationRejected' => [
        'App\Listeners\LogAuthRejected',
    ],
    
    'Adldap\Laravel\Events\AuthenticatedModelTrashed' => [
        'App\Listeners\LogUserModelIsTrashed',
    ],
    
    'Adldap\Laravel\Events\AuthenticatedWithCredentials' => [
         'App\Listeners\LogAuthWithCredentials',
    ],
    
    'Adldap\Laravel\Events\AuthenticatedWithWindows' => [
        'App\Listeners\LogSSOAuth',
    ],
    
    'Adldap\Laravel\Events\DiscoveredWithCredentials' => [
         'App\Listeners\LogAuthUserLocated',
    ],
    
    'Adldap\Laravel\Events\Importing' => [
        'App\Listeners\LogImportingUser',
    ],
    
    'Adldap\Laravel\Events\Synchronized' => [
         'App\Listeners\LogSynchronizedUser',
    ],
    
    'Adldap\Laravel\Events\Synchronizing' => [
        'App\Listeners\LogSynchronizingUser',
    ],

];
```

> **Note:** For some real examples, you can browse the listeners located
> in: `vendor/adldap2/adldap2-laravel/src/Listeners` and see their usage.
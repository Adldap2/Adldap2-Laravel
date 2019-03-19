<?php

namespace Adldap\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Adldap\Laravel\Resolvers\ResolverInterface;

/**
 * @method static void setConnection(string $connection)
 * @method static \Adldap\Models\Model|null byId(string $identifier)
 * @method static \Adldap\Models\User|null byCredentials(array $credentials)
 * @method static \Adldap\Models\User|null byModel(\Illuminate\Contracts\Auth\Authenticatable $model)
 * @method static boolean authenticate(\Adldap\Models\User $user, array $credentials = [])
 * @method static \Adldap\Query\Builder query()
 * @method static string getLdapDiscoveryAttribute()
 * @method static string getLdapAuthAttribute()
 * @method static string getDatabaseUsernameColumn()
 * @method static string getDatabaseIdColumn()
 *
 * @see \Adldap\Laravel\Resolvers\UserResolver
 */
class Resolver extends Facade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor()
    {
        return ResolverInterface::class;
    }
}

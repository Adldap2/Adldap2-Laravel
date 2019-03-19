# Testing

To test that your configured LDAP connection is being authenticated against, you can utilize the `Adldap\Laravel\Facades\Resolver` facade.

Using the facade, you can mock certain methods to return mock LDAP users
and pass or deny authentication to test different scenarios.

```php
<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\Facades\Resolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithFaker;

class AuthTest extends TestCase
{
    use WithFaker;

    /**
     * Returns a new LDAP user model.
     *
     * @param array $attributes
     *
     * @return \Adldap\Models\User
     */
    protected function makeLdapUser(array $attributes = [])
    {
        $provider = config('ldap_auth.connection');

        return Adldap::getProvider($provider)->make()->user($attributes);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_ldap_authentication_works()
    {
        $credentials = ['email' => 'jdoe@email.com', 'password' => '12345'];

        $user = $this->makeLdapUser([
            'objectguid'            => [$this->faker->uuid],
            'cn'                    => ['John Doe'],
            'userprincipalname'     => ['jdoe@email.com'],
        ]);

        Resolver::shouldReceive('byCredentials')->once()->with($credentials)->andReturn($user)
            ->shouldReceive('getDatabaseIdColumn')->twice()->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname')
            ->shouldReceive('authenticate')->once()->andReturn(true);

        $this->post(route('login'), $credentials)->assertRedirect('/dashboard');

        $this->assertInstanceOf(User::class, Auth::user());
    }
}
```

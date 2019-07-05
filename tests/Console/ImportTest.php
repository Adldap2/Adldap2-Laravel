<?php

namespace Adldap\Laravel\Tests\Console;

use Mockery as m;
use Adldap\Query\Builder;
use Adldap\Laravel\Facades\Resolver;
use Illuminate\Support\Facades\Hash;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Laravel\Tests\DatabaseTestCase;
use Adldap\Models\Attributes\AccountControl;

class ImportTest extends DatabaseTestCase
{
    public function test_importing_one_user()
    {
        $b = m::mock(Builder::class);

        $u = $this->makeLdapUser();

        $b->shouldReceive('findOrFail')->once()->with('jdoe')->andReturn($u);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getDatabaseIdColumn')->twice()->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname');

        $this->artisan('adldap:import', ['user' => 'jdoe', '--no-interaction' => true])
            ->expectsOutput("Found user 'John Doe'.")
            ->expectsOutput('Successfully imported / synchronized 1 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'jdoe@email.com']);
    }

    public function test_importing_multiple_users()
    {
        $b = m::mock(Builder::class);

        $users = [
            $this->makeLdapUser([
                'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
                'samaccountname'    => ['johndoe'],
                'userprincipalname' => ['johndoe@email.com'],
                'mail'              => ['johndoe@email.com'],
                'cn'                => ['John Doe'],
            ]),
            $this->makeLdapUser([
                'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b1'],
                'samaccountname'    => ['janedoe'],
                'userprincipalname' => ['janedoe@email.com'],
                'mail'              => ['janedoe@email.com'],
                'cn'                => ['Jane Doe'],
            ]),
        ];

        $b->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn($users);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getDatabaseIdColumn')->times(4)->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->twice()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->twice()->andReturn('userprincipalname');

        $this->artisan('adldap:import', ['--no-interaction' => true])
            ->expectsOutput('Found 2 user(s).')
            ->expectsOutput('Successfully imported / synchronized 2 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'johndoe@email.com']);
        $this->assertDatabaseHas('users', ['email' => 'janedoe@email.com']);
    }

    public function test_questions_asked_with_interaction()
    {
        $b = m::mock(Builder::class);

        $u = $this->makeLdapUser();

        $b->shouldReceive('findOrFail')->once()->with('jdoe')->andReturn($u);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getDatabaseIdColumn')->twice()->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname');

        $this->artisan('adldap:import', ['user' => 'jdoe'])
            ->expectsOutput("Found user 'John Doe'.")
            ->expectsQuestion('Would you like to display the user(s) to be imported / synchronized?', 'no')
            ->expectsQuestion('Would you like these users to be imported / synchronized?', 'yes')
            ->expectsOutput('Successfully imported / synchronized 1 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'jdoe@email.com']);
    }

    public function test_model_will_be_restored_when_ldap_account_is_active()
    {
        $user = $this->makeLdapUser();

        $model = TestUser::create([
            'objectguid' => $user->getConvertedGuid(),
            'email'      => $user->getUserPrincipalName(),
            'name'       => $user->getCommonName(),
            'password'   => Hash::make('password'),
        ]);

        $model->delete();

        $this->assertTrue($model->trashed());

        $user->setUserAccountControl((new AccountControl())->accountIsNormal());

        $this->assertTrue($user->isEnabled());

        $b = m::mock(Builder::class);

        $b->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn([$user]);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getDatabaseIdColumn')->twice()->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname');

        $this->artisan('adldap:import', ['--restore' => true, '--no-interaction' => true])
            ->expectsOutput("Found user 'John Doe'.")
            ->expectsOutput('Successfully imported / synchronized 1 user(s).')
            ->assertExitCode(0);

        $this->assertFalse($model->fresh()->trashed());
    }

    public function test_model_will_be_soft_deleted_when_ldap_account_is_disabled()
    {
        $user = $this->makeLdapUser();

        $user->setUserAccountControl((new AccountControl())->accountIsDisabled());

        $this->assertTrue($user->isDisabled());

        $model = TestUser::create([
            'objectguid' => $user->getConvertedGuid(),
            'email'      => 'jdoe@email.com',
            'name'       => 'John Doe',
            'password'   => Hash::make('password'),
        ]);

        $this->assertFalse($model->trashed());

        $b = m::mock(Builder::class);

        $b->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn([$user]);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getDatabaseIdColumn')->twice()->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname');

        $this->artisan('adldap:import', ['--delete' => true, '--no-interaction' => true])
            ->expectsOutput("Found user 'John Doe'.")
            ->expectsOutput('Successfully imported / synchronized 1 user(s).')
            ->assertExitCode(0);

        $this->assertTrue($model->fresh()->trashed());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_filter_option_applies_to_ldap_query()
    {
        $f = '(samaccountname=jdoe)';

        $b = m::mock(Builder::class);

        $b
            ->shouldReceive('rawFilter')->once()->with($f)->andReturnSelf()
            ->shouldReceive('paginate')->once()->andReturnSelf()
            ->shouldReceive('getResults')->once()->andReturn([]);

        Resolver::shouldReceive('query')->once()->andReturn($b);

        $this->artisan('adldap:import', ['--filter' => $f, '--no-interaction' => true]);
    }
}

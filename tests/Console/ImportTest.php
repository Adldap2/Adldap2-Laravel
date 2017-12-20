<?php

namespace Adldap\Laravel\Tests\Console;

use Mockery as m;
use Adldap\Query\Builder;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Tests\Models\User;
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
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('mail')
            ->shouldReceive('getEloquentUsernameAttribute')->once()->andReturn('email');

        $r = $this->artisan('adldap:import', ['user' => 'jdoe', '--no-interaction']);

        $this->assertEquals(0, $r);
        $this->assertDatabaseHas('users', ['email' => 'jdoe@email.com']);
    }

    public function test_importing_multiple_users()
    {
        $b = m::mock(Builder::class);

        $users = [
            $this->makeLdapUser([
                'samaccountname' => ['johndoe'],
                'userprincipalname' => ['johndoe@email.com'],
                'mail'           => ['johndoe@email.com'],
                'cn'             => ['John Doe'],
            ]),
            $this->makeLdapUser([
                'samaccountname' => ['janedoe'],
                'userprincipalname' => ['janedoe@email.com'],
                'mail'           => ['janedoe@email.com'],
                'cn'             => ['Jane Doe'],
            ])
        ];

        $b->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn($users);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getLdapDiscoveryAttribute')->twice()->andReturn('mail')
            ->shouldReceive('getEloquentUsernameAttribute')->twice()->andReturn('email');

        $r = $this->artisan('adldap:import', ['--no-interaction']);

        $this->assertEquals(0, $r);
        $this->assertDatabaseHas('users', ['email' => 'johndoe@email.com']);
        $this->assertDatabaseHas('users', ['email' => 'janedoe@email.com']);
    }

    public function test_model_will_be_restored_when_ldap_account_is_active()
    {
        $model = User::create([
            'email' => 'jdoe@email.com',
            'name' => 'John Doe',
            'password' => bcrypt('password'),
        ]);

        $model->delete();

        $this->assertTrue($model->trashed());

        $user = $this->makeLdapUser();

        $ac = new AccountControl();

        $ac->accountIsNormal();

        $user->setUserAccountControl($ac);

        $this->assertTrue($user->isEnabled());

        $b = m::mock(Builder::class);

        $b->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn([$user]);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('mail')
            ->shouldReceive('getEloquentUsernameAttribute')->once()->andReturn('email');

        $r = $this->artisan('adldap:import', ['--restore' => true, '--no-interaction' => true]);

        $this->assertEquals(0, $r);
        $this->assertFalse($model->fresh()->trashed());
    }

    public function test_model_will_be_soft_deleted_when_ldap_account_is_disabled()
    {
        $model = User::create([
            'email' => 'jdoe@email.com',
            'name' => 'John Doe',
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse($model->trashed());

        $user = $this->makeLdapUser();

        $ac = new AccountControl();

        $ac->accountIsDisabled();

        $user->setUserAccountControl($ac);

        $this->assertTrue($user->isDisabled());

        $b = m::mock(Builder::class);

        $b->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn([$user]);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('mail')
            ->shouldReceive('getEloquentUsernameAttribute')->once()->andReturn('email');

        $r = $this->artisan('adldap:import', ['--delete' => true, '--no-interaction' => true]);

        $this->assertEquals(0, $r);
        $this->assertTrue($model->fresh()->trashed());
    }

    public function test_filter_option_applies_to_ldap_query()
    {
        $f = '(samaccountname=jdoe)';

        $b = m::mock(Builder::class);

        $b
            ->shouldReceive('rawFilter')->once()->with($f)->andReturn($b)
            ->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn([]);

        Resolver::shouldReceive('query')->once()->andReturn($b);

        $r = $this->artisan('adldap:import', ['--filter' => $f, '--no-interaction' => true]);

        $this->assertEquals(0, $r);
    }
}

<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\LaratrustUserInterface;
use Laratrust\Contracts\Ownable;
use Laratrust\Traits\LaratrustUserTrait;
use Mockery as m;

class LaratrustUserTest extends UserTest
{
    public function testRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $morphToMany = m::mock(new stdClass());
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('morphToMany')
            ->with('role', 'user', 'roles', 'user_id', 'role_id')
            ->andReturn($morphToMany)
            ->twice();
        $morphToMany->shouldReceive('withPivot')
            ->with('team_id')
            ->once()
            ->andReturn($morphToMany);

        Config::shouldReceive('get')->once()->with('laratrust.use_teams')->andReturn(false)->ordered();
        Config::shouldReceive('get')->once()->with('laratrust.use_teams')->andReturn(true)->ordered();
        Config::shouldReceive('get')->twice()->with('laratrust.models.role')->andReturn('role');
        Config::shouldReceive('get')->twice()->with('laratrust.tables.role_user')->andReturn('roles');
        Config::shouldReceive('get')->twice()->with('laratrust.foreign_keys.user')->andReturn('user_id');
        Config::shouldReceive('get')->twice()->with('laratrust.foreign_keys.role')->andReturn('role_id');
        Config::shouldReceive('get')->once()->with('laratrust.foreign_keys.team')->andReturn('team_id');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($morphToMany, $user->roles());
        $this->assertSame($morphToMany, $user->roles());
    }

    public function testPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $morphToMany = m::mock(new stdClass());
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('morphToMany')
            ->with('permission', 'user', 'permissions', 'user_id', 'permission_id')
            ->andReturn($morphToMany)
            ->twice();
        $morphToMany->shouldReceive('withPivot')
            ->with('team_id')
            ->andReturn($morphToMany)
            ->once();

        Config::shouldReceive('get')->once()->with('laratrust.use_teams')->andReturn(false)->ordered();
        Config::shouldReceive('get')->once()->with('laratrust.use_teams')->andReturn(true)->ordered();
        Config::shouldReceive('get')->twice()->with('laratrust.models.permission')->andReturn('permission');
        Config::shouldReceive('get')->twice()->with('laratrust.tables.permission_user')->andReturn('permissions');
        Config::shouldReceive('get')->twice()->with('laratrust.foreign_keys.user')->andReturn('user_id');
        Config::shouldReceive('get')->twice()->with('laratrust.foreign_keys.permission')->andReturn('permission_id');
        Config::shouldReceive('get')->once()->with('laratrust.foreign_keys.team')->andReturn('team_id');
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($morphToMany, $user->permissions());
        $this->assertSame($morphToMany, $user->permissions());
    }

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = $this->mockTeam('TeamA');
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');
        $roleC = $this->mockRole('RoleC', $team->id);

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB, $roleC];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->times(19)->andReturn(true)->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->times(3)->andReturn(false)->ordered();
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(18)->andReturn('1440');
        Cache::shouldReceive('remember')->times(18)->andReturn($user->roles);
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->times(14)->andReturn('team_id');
        Config::shouldReceive('get')->with('laratrust.models.team')->times(5)->andReturn($team);
        $team->shouldReceive('where')->with('name', 'TeamA')->times(5)->andReturn($team);
        $team->shouldReceive('first')->times(5)->andReturn($team);
        $team->shouldReceive('getKey')->times(5)->andReturn($team->id);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasRole([]));
        $this->assertTrue($user->hasRole('RoleA'));
        $this->assertTrue($user->hasRole('RoleB'));
        $this->assertFalse($user->hasRole('RoleC'));
        $this->assertTrue($user->hasRole('RoleC', 'TeamA'));
        $this->assertFalse($user->hasRole('RoleA', 'TeamA'));

        $this->assertTrue($user->hasRole('RoleA|RoleB'));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleB']));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC']));
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC'], 'TeamA'));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], 'TeamA', true));
        $this->assertFalse($user->hasRole(['RoleA', 'RoleC'], true));
        $this->assertFalse($user->hasRole(['RoleC', 'RoleD']));
        // Not using teams
        $this->assertTrue($user->hasRole(['RoleA', 'RoleC'], 'TeamA'));
        $this->assertFalse($user->hasRole(['RoleC', 'RoleD'], true));
    }

    public function testHasPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = $this->mockTeam('TeamA');

        $permA = $this->mockPermission('manage_a');
        $permB = $this->mockPermission('manage_b');
        $permC = $this->mockPermission('manage_c', $team->id);
        $permD = $this->mockPermission('manage_d');

        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB', $team->id);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB];

        $user = new HasRoleUser();
        $user->roles = [$roleA, $roleB];
        $user->permissions = [$permC, $permD];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->times(56)->andReturn(true)->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->times()->andReturn(false)->ordered();
        $roleA->shouldReceive('cachedPermissions')->times(13)->andReturn($roleA->perms);
        $roleB->shouldReceive('cachedPermissions')->times(3 )->andReturn($roleB->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(34)->andReturn('1440');
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->times(52)->andReturn('team_id');
        Config::shouldReceive('get')->with('laratrust.models.team')->times(4)->andReturn($team);
        $team->shouldReceive('where')->with('name', 'TeamA')->times(4)->andReturn($team);
        $team->shouldReceive('first')->times(4)->andReturn($team);
        $team->shouldReceive('getKey')->times(4)->andReturn($team->id);

        Cache::shouldReceive('remember')
            ->with(
                "laratrust_permissions_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(19)->andReturn($user->permissions);
        Cache::shouldReceive('remember')
            ->with(
                "laratrust_roles_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(15)->andReturn($user->roles);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasPermission([]));
        $this->assertTrue($user->hasPermission('manage_a'));
        $this->assertTrue($user->hasPermission('manage_b', 'TeamA'));
        $this->assertTrue($user->hasPermission('manage_c', 'TeamA'));
        $this->assertTrue($user->hasPermission('manage_d'));
        $this->assertFalse($user->hasPermission('manage_e'));

        $this->assertTrue($user->hasPermission(['manage_a', 'manage_b', 'manage_c', 'manage_d', 'manage_e']));
        $this->assertTrue($user->hasPermission('manage_a|manage_b|manage_c|manage_d|manage_e'));
        $this->assertTrue($user->hasPermission(['manage_a', 'manage_d'], true));
        $this->assertFalse($user->hasPermission(['manage_a', 'manage_b', 'manage_d'], true));
        $this->assertFalse($user->hasPermission(['manage_a', 'manage_b', 'manage_d'], 'TeamA', true));
        $this->assertFalse($user->hasPermission(['manage_a', 'manage_b', 'manage_e'], true));
        $this->assertFalse($user->hasPermission(['manage_e', 'manage_f']));
        // Not using teams
        $this->assertTrue($user->hasPermission(['manage_a', 'manage_b', 'manage_d'], 'TeamA', true));
    }

    public function testCan()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasPermission')->with('manage_user', null, false)->andReturn(true)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->can('manage_user'));
    }

    public function testIsAbleTo()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasPermission')->with('manage_user', null, false)->andReturn(true)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->isAbleTo('manage_user'));
    }

    public function testHasPermissionWithPlaceholderSupport()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = $this->mockTeam('TeamA');

        $permA = $this->mockPermission('admin.posts');
        $permB = $this->mockPermission('admin.pages');
        $permC = $this->mockPermission('admin.users');
        $permD = $this->mockPermission('config.things', $team->id);

        $role = $this->mockRole('Role');

        $role->perms = [$permA, $permB, $permC];

        $user = new HasRoleUser();
        $user->roles = [$role];
        $user->permissions = [$permD];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
       Config::shouldReceive('get')->with('laratrust.use_teams')->times(17)->andReturn(true)->ordered();
        $role->shouldReceive('cachedPermissions')->times(6)->andReturn($role->perms);
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(15)->andReturn('1440');
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->times(15)->andReturn('team_id');
        Config::shouldReceive('get')->with('laratrust.models.team')
            ->twice()
            ->andReturn($team);
        $team->shouldReceive('where')->with('name', 'TeamA')->twice()->andReturn($team);
        $team->shouldReceive('first')->twice()->andReturn($team);
        $team->shouldReceive('getKey')->twice()->andReturn($team->id);

        Cache::shouldReceive('remember')
            ->with(
                "laratrust_permissions_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(8)->andReturn($user->permissions);
        Cache::shouldReceive('remember')
            ->with(
                "laratrust_roles_for_user_{$user->getKey()}",
                1440,
                m::any()
            )->times(7)->andReturn($user->roles);


        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasPermission('admin.posts'));
        $this->assertTrue($user->hasPermission('admin.pages'));
        $this->assertTrue($user->hasPermission('admin.users'));
        $this->assertFalse($user->hasPermission('admin.config', 'TeamA'));

        $this->assertTrue($user->hasPermission(['admin.*']));
        $this->assertTrue($user->hasPermission(['admin.*']));
        $this->assertTrue($user->hasPermission(['config.*'], 'TeamA'));
        $this->assertFalse($user->hasPermission(['site.*']));
    }


    public function testMagicCanPermissionMethod()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.magic_can_method_case')->andReturn('kebab_case')->once()->ordered();
        $user->shouldReceive('hasPermission')->with('manage-user', null, false)->andReturn(true)->once()->ordered();
        
        Config::shouldReceive('get')->with('laratrust.magic_can_method_case')->andReturn('snake_case')->once()->ordered();
        $user->shouldReceive('hasPermission')->with('manage_user', null, false)->andReturn(true)->once()->ordered();
        
        Config::shouldReceive('get')->with('laratrust.magic_can_method_case')->andReturn('camel_case')->once()->ordered();
        $user->shouldReceive('hasPermission')->with('manageUser', null, false)->andReturn(true)->once()->ordered();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->publicMagicCan('canManageUser'));
        $this->assertTrue($user->publicMagicCan('canManageUser'));
        $this->assertTrue($user->publicMagicCan('canManageUser'));
    }

    public function testAttachRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $role = $this->mockRole('admin');
        $team = $this->mockTeam('TeamA');
        $user = m::mock('HasRoleUser')->makePartial();
        $roleArray = ['id' => 1];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(true)->times(7)->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(false)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->andReturn('team_id')->times(14);
        Config::shouldReceive('get')->with('laratrust.foreign_keys.role')->andReturn('role_id')->times(7);
        $user->shouldReceive('roles->wherePivot->wherePivot->count')->andReturn(0)->times(7);
        $role->shouldReceive('getKey')->andReturn(1)->times(7);
        $user->shouldReceive('roles->attach')->with(1, m::anyOf(['team_id' => null], ['team_id' => 1]))->times(7)->ordered();
        $user->shouldReceive('roles->attach')->with(1, [])->twice()->ordered();
        Cache::shouldReceive('forget')->times(18);
        Config::shouldReceive('get')->with('laratrust.models.role')->andReturn($role)->once();
        $role->shouldReceive('where')->with('name', 'admin')->andReturn($role)->once();
        $role->shouldReceive('firstOrFail')->andReturn($role)->once();
        $team->shouldReceive('getKey')->andReturn($team->id)->twice();
        Config::shouldReceive('get')->with('laratrust.models.team')->andReturn($team)->once();
        $team->shouldReceive('where')->with('name', 'TeamA')->andReturn($team)->once();
        $team->shouldReceive('firstOrFail')->andReturn($team)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can attach role by passing an object
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($role));
        // Can attach role by passing an id
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($role->id));
        // Can attach role by passing an array with 'id' => $id
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($roleArray));
        // Can attach role by passing the role name
        $this->assertInstanceOf('HasRoleUser', $user->attachRole('admin'));// Can attach role by passing the role and team
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($role, $team));
        // Can attach role by passing the role and team id
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($role, $team->id));
        // Can attach role by passing the role and team name
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($role, 'TeamA'));
        // Not using teams
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($role));
        $this->assertInstanceOf('HasRoleUser', $user->attachRole($role, 'TeamA'));
        
    }

    public function testDetachRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $role = $this->mockRole('admin');
        $team = $this->mockTeam('TeamA');
        $user = m::mock('HasRoleUser')->makePartial();
        $roleArray = ['id' => 1];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(true)->times(7)->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(false)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->andReturn('team_id')->times(7);
        $user->shouldReceive('roles')->andReturn($user)->times(9);
        $user->shouldReceive('wherePivot')->with('team_id', m::anyOf(1, null))->andReturn($user)->times(7);
        $user->shouldReceive('detach')->with(1)->times(9);
        Cache::shouldReceive('forget')->times(18);
        $role->shouldReceive('getKey')->andReturn(1)->times(7);
        Config::shouldReceive('get')->with('laratrust.models.role')->andReturn($role)->once();
        $role->shouldReceive('where')->with('name', 'admin')->andReturn($role)->once();
        $role->shouldReceive('firstOrFail')->andReturn($role)->once();
        $team->shouldReceive('getKey')->andReturn($team->id)->twice();
        Config::shouldReceive('get')->with('laratrust.models.team')->andReturn($team)->once();
        $team->shouldReceive('where')->with('name', 'TeamA')->andReturn($team)->once();
        $team->shouldReceive('firstOrFail')->andReturn($team)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can detach role by passing an object
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($role));
        // Can detach role by passing an id
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($role->id));
        // Can detach role by passing an array with 'id' => $id
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($roleArray));
        // Can detach role by passing the role name
        $this->assertInstanceOf('HasRoleUser', $user->detachRole('admin'));// Can detach role by passing the role and team
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($role, $team));
        // Can detach role by passing the role and team id
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($role, $team->id));
        // Can detach role by passing the role and team name
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($role, 'TeamA'));
        // Not using teams
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($role));
        $this->assertInstanceOf('HasRoleUser', $user->detachRole($role, 'TeamA'));
    }

    public function testAttachRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('attachRole')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->attachRoles([1, 2, 3]));
        $this->assertInstanceOf('HasRoleUser', $user->attachRoles([1, 2, 3], 'TeamA'));
    }

    public function testDetachRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('roles->get')->andReturn([1, 2, 3])->once();
        $user->shouldReceive('detachRole')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(9);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->detachRoles([1, 2, 3]));
        $this->assertInstanceOf('HasRoleUser', $user->detachRoles([]));
        $this->assertInstanceOf('HasRoleUser', $user->detachRoles([1, 2, 3], 'TeamA'));
    }

    public function testSyncRoles()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $rolesIds = [1, 2, 3];
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(true)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(false)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->andReturn('team_id')->times(6);
        $user->shouldReceive('roles')->andReturn($user)->times(4);
        $user->shouldReceive('sync')->with([
            1 => ['team_id' => null],
            2 => ['team_id' => null],
            3 => ['team_id' => null]
        ])->once()->ordered();
        $user->shouldReceive('sync')->with([
            1 => ['team_id' => 'TeamA'],
            2 => ['team_id' => 'TeamA'],
            3 => ['team_id' => 'TeamA']
        ])->once()->ordered();
        $user->shouldReceive('sync')->with([1, 2, 3])->twice()->ordered();
        Cache::shouldReceive('forget')->times(8);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->syncRoles($rolesIds));
        $this->assertInstanceOf('HasRoleUser', $user->syncRoles($rolesIds, 'TeamA'));
        // Not using teams
        $this->assertInstanceOf('HasRoleUser', $user->syncRoles($rolesIds));
        $this->assertInstanceOf('HasRoleUser', $user->syncRoles($rolesIds, 'TeamA'));
    }

    public function testAttachPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permission = $this->mockPermission('admin.users');
        $team = $this->mockTeam('TeamA');
        $user = m::mock('HasRoleUser')->makePartial();
        $permissionArray = ['id' => 1];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(true)->times(7)->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(false)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->andReturn('team_id')->times(14);
        Config::shouldReceive('get')->with('laratrust.foreign_keys.permission')->andReturn('role_id')->times(7);
        $user->shouldReceive('permissions->wherePivot->wherePivot->count')->andReturn(0)->times(7);
        $permission->shouldReceive('getKey')->andReturn(1)->times(7);
        $user->shouldReceive('permissions->attach')->with(1, m::anyOf(['team_id' => null], ['team_id' => 1]))->times(7)->ordered();
        $user->shouldReceive('permissions->attach')->with(1, [])->twice()->ordered();
        Cache::shouldReceive('forget')->times(18);
        Config::shouldReceive('get')->with('laratrust.models.permission')->andReturn($permission)->once();
        $permission->shouldReceive('where')->with('name', 'admin.users')->andReturn($permission)->once();
        $permission->shouldReceive('firstOrFail')->andReturn($permission)->once();
        $team->shouldReceive('getKey')->andReturn($team->id)->twice();
        Config::shouldReceive('get')->with('laratrust.models.team')->andReturn($team)->once();
        $team->shouldReceive('where')->with('name', 'TeamA')->andReturn($team)->once();
        $team->shouldReceive('firstOrFail')->andReturn($team)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can attach permission by passing an object
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permission));
        // Can attach role by passing an id
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permission->id));
        // Can attach role by passing an array with 'id' => $id
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permissionArray));
        // Can attach role by passing the role name
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission('admin.users'));// Can attach role by passing the role and team
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permission, $team));
        // Can attach role by passing the role and team id
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permission, $team->id));
        // Can attach role by passing the role and team name
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permission, 'TeamA'));
        // Not using teams
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permission));
        $this->assertInstanceOf('HasRoleUser', $user->attachPermission($permission, 'TeamA'));
    }

    public function testDetachPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permission = $this->mockPermission('admin.users');
        $team = $this->mockTeam('TeamA');
        $user = m::mock('HasRoleUser')->makePartial();
        $permissionArray = ['id' => 1];

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(true)->times(7)->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(false)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->andReturn('team_id')->times(7);
        $user->shouldReceive('permissions')->andReturn($user)->times(9);
        $user->shouldReceive('wherePivot')->with('team_id', m::anyOf(1, null))->andReturn($user)->times(7);
        $user->shouldReceive('detach')->with(1)->times(9);
        Cache::shouldReceive('forget')->times(18);
        $permission->shouldReceive('getKey')->andReturn(1)->times(7);
        Config::shouldReceive('get')->with('laratrust.models.permission')->andReturn($permission)->once();
        $permission->shouldReceive('where')->with('name', 'admin.users')->andReturn($permission)->once();
        $permission->shouldReceive('firstOrFail')->andReturn($permission)->once();
        $team->shouldReceive('getKey')->andReturn($team->id)->twice();
        Config::shouldReceive('get')->with('laratrust.models.team')->andReturn($team)->once();
        $team->shouldReceive('where')->with('name', 'TeamA')->andReturn($team)->once();
        $team->shouldReceive('firstOrFail')->andReturn($team)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Can detach role by passing an object
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permission));
        // Can detach role by passing an id
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permission->id));
        // Can detach role by passing an array with 'id' => $id
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permissionArray));
        // Can detach role by passing the role name
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission('admin.users'));// Can detach role by passing the role and team
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permission, $team));
        // Can detach role by passing the role and team id
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permission, $team->id));
        // Can detach role by passing the role and team name
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permission, 'TeamA'));
        // Not using teams
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permission));
        $this->assertInstanceOf('HasRoleUser', $user->detachPermission($permission, 'TeamA'));
    }

    public function testAttachPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('attachPermission')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(6);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->attachPermissions([1, 2, 3]));
        $this->assertInstanceOf('HasRoleUser', $user->attachPermissions([1, 2, 3], 'TeamA'));
    }

    public function testDetachPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('permissions->get')->andReturn([1, 2, 3]);
        $user->shouldReceive('detachPermission')->with(m::anyOf(1, 2, 3), m::anyOf(null, 'TeamA'))->times(9);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->detachPermissions( [1, 2, 3]));
        $this->assertInstanceOf('HasRoleUser', $user->detachPermissions([]));
        $this->assertInstanceOf('HasRoleUser', $user->detachPermissions([1, 2, 3], 'TeamA'));
    }

    public function testSyncPermissions()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permissionsIds = [1, 2, 3];
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(true)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.use_teams')->andReturn(false)->twice()->ordered();
        Config::shouldReceive('get')->with('laratrust.foreign_keys.team')->andReturn('team_id')->times(6);
        $user->shouldReceive('permissions')->andReturn($user)->times(4);
        $user->shouldReceive('sync')->with([
            1 => ['team_id' => null],
            2 => ['team_id' => null],
            3 => ['team_id' => null]
        ])->once()->ordered();
        $user->shouldReceive('sync')->with([
            1 => ['team_id' => 'TeamA'],
            2 => ['team_id' => 'TeamA'],
            3 => ['team_id' => 'TeamA']
        ])->once()->ordered();
        $user->shouldReceive('sync')->with([1, 2, 3])->twice()->ordered();
        Cache::shouldReceive('forget')->times(8);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf('HasRoleUser', $user->syncPermissions($permissionsIds));
        $this->assertInstanceOf('HasRoleUser', $user->syncPermissions($permissionsIds, 'TeamA'));
        // Not using teams
        $this->assertInstanceOf('HasRoleUser', $user->syncPermissions($permissionsIds));
        $this->assertInstanceOf('HasRoleUser', $user->syncPermissions($permissionsIds, 'TeamA'));
    }

    public function testUserOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();
        $className = snake_case(get_class($user)) . '_id';
        
        $post = new stdClass();
        $post->$className = $user->getKey();

        $post2 = new stdClass();
        $post2->$className = 9;

        $ownableObject = new OwnableObject;

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->owns($post));
        $this->assertFalse($user->owns($post2));
        $this->assertFalse($user->owns($ownableObject));
    }

    public function testUserOwnsaPostModelCustomKey()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();
        $post->UserId = $user->getKey();

        $post2 = new stdClass();
        $post2->UserId = 9;

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->owns($post, 'UserId'));
        $this->assertFalse($user->owns($post2, 'UserId'));
    }

    public function testUserHasRoleAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = $this->mockTeam('TeamA');
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasRole')->with('editor', null, false)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($post, null)->andReturn(true)->once();
        $user->shouldReceive('hasRole')->with('regular-user', null, false)->andReturn(false)->once();
        $user->shouldReceive('hasRole')->with('administrator', null, true)->andReturn(true)->once();
        $user->shouldReceive('hasRole')->with('team-member', $team, true)->andReturn(false)->once();
        $user->shouldReceive('owns')->with($post, 'UserID')->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->hasRoleAndOwns('editor', $post));
        $this->assertFalse($user->hasRoleAndOwns('regular-user', $post));
        $this->assertFalse($user->hasRoleAndOwns('administrator', $post, [
            'requireAll' => true, 'foreignKeyName' => 'UserID'
        ]));
        $this->assertFalse($user->hasRoleAndOwns('team-member', $post, [
            'requireAll' => true,
            'foreignKeyName' => 'UserID',
            'team' => $team
        ]));
    }

    public function testUserCanAndOwnsaPostModel()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $team = $this->mockTeam('TeamA');
        $user = m::mock('HasRoleUser')->makePartial();
        $post = new stdClass();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasPermission')->with('edit-post', null, false)->andReturn(true)->once();
        $user->shouldReceive('owns')->with($post, null)->andReturn(true)->once();
        $user->shouldReceive('hasPermission')->with('update-post', null, false)->andReturn(false)->once();
        $user->shouldReceive('hasPermission')->with('enhance-post', null, true)->andReturn(true)->once();
        $user->shouldReceive('hasPermission')->with('edit-team', $team, true)->andReturn(false)->once();
        $user->shouldReceive('owns')->with($post, 'UserID')->andReturn(false)->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($user->canAndOwns('edit-post', $post));
        $this->assertFalse($user->canAndOwns('update-post', $post));
        $this->assertFalse($user->canAndOwns('enhance-post', $post, [
            'requireAll' => true, 'foreignKeyName' => 'UserID'
        ]));
        $this->assertFalse($user->canAndOwns('edit-team', $post, [
            'requireAll' => true,
            'foreignKeyName' => 'UserID',
            'team' => $team
        ]));
    }

    public function testAllPermissions()
    {
        $user = m::mock('HasRoleUser')->makePartial();
        $roleA = $this->mockRole('RoleA');
        $roleB = $this->mockRole('RoleB');
        $permissionA = $this->mockPermission('PermA');
        $permissionB = $this->mockPermission('PermB');
        $permissionC = $this->mockPermission('PermC');

        $roleA->permissions = [$permissionA, $permissionB];
        $roleB->permissions = [$permissionB, $permissionC];
        $user->permissions = [$permissionB, $permissionC];

        $user->shouldReceive('roles->with->get')->andReturn(new Illuminate\Support\Collection([$roleA, $roleB]));
        $user->shouldReceive('cachedPermissions')->andReturn(new Illuminate\Support\Collection($user->permissions));

        $this->assertSame(
            ['PermA', 'PermB', 'PermC'],
            $user->allPermissions()->sortBy('name')->pluck('name')->all()
        );
    }

    public function testScopeWhereRoleIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $query = m::mock();
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $query->shouldReceive('whereHas')
            ->with('roles', m::any())
            ->once()
            ->andReturn($query);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(get_class($query), $user->scopeWhereRoleIs($query, 'admin'));
    }

    public function testScopeWherePermissionIs()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $query = m::mock();
        $user = m::mock('HasRoleUser')->makePartial();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $query->shouldReceive('whereHas')
            ->with('roles.permissions', m::any())
            ->once()
            ->andReturn($query);

        $query->shouldReceive('orWhereHas')
            ->with('permissions', m::any())
            ->once()
            ->andReturn($query);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertInstanceOf(get_class($query), $user->scopeWherePermissionIs($query, 'create-users'));
    }

    public function testBootLaratrustUserTrait()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $user = m::mock('HasRoleUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('bootLaratrustUserTrait');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        HasRoleUser::bootLaratrustUserTrait();
    }
}

class HasRoleUser extends Model implements LaratrustUserInterface
{
    use LaratrustUserTrait;
    use SoftDeletes;

    public $roles;
    public $permissions;
    public $primaryKey;

    public function __construct()
    {
        $this->primaryKey = 'id';
        $this->setAttribute('id', 4);
    }

    public function getKey()
    {
        return $this->id;
    }

    public function publicMagicCan($method) {
        return $this->handleMagicCan($method, []);
    }
}

class OwnableObject implements Ownable
{
    public function ownerKey()
    {
        return 1;
    }
}

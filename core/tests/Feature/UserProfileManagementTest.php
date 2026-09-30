<?php

use App\Models\Permissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::table('webmaster_settings')->insert([
        'id' => 1,
        'version' => '13',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

function profilePermissionGroup(array $overrides = []): Permissions
{
    $group = new Permissions;
    $group->name = $overrides['name'] ?? 'Test group';
    $group->view_status = $overrides['view_status'] ?? false;
    $group->roles_status = $overrides['roles_status'] ?? false;
    $group->webmaster_status = $overrides['webmaster_status'] ?? false;
    $group->status = true;
    $group->save();

    return $group;
}

function profileUser(Permissions $group, array $overrides = []): User
{
    static $sequence = 0;
    $sequence++;

    $user = new User;
    $user->name = $overrides['name'] ?? 'Profile User '.$sequence;
    $user->email = $overrides['email'] ?? 'profile'.$sequence.'@example.test';
    $user->password = Hash::make($overrides['plain_password'] ?? 'password123');
    $user->permissions_id = $group->id;
    $user->status = $overrides['status'] ?? true;
    $user->created_by = $overrides['created_by'] ?? null;
    $user->connect_email = $overrides['connect_email'] ?? null;
    $user->connect_password = $overrides['connect_password'] ?? null;
    $user->access_token = $overrides['access_token'] ?? null;
    $user->table_columns = $overrides['table_columns'] ?? null;
    $user->save();

    return $user;
}

test('an authenticated user can manage their own profile without role management permission', function () {
    $limited = profilePermissionGroup(['view_status' => true]);
    $user = profileUser($limited);

    $this->actingAs($user)
        ->get(route('userProfile.show', $user))
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee(__('backend.mcpServer'));

    $this->actingAs($user)
        ->get(route('usersEdit', $user))
        ->assertRedirect(route('userProfile.show', $user));
});

test('personal access token indexes use prefix-safe explicit names', function () {
    $indexNames = collect(Schema::getIndexes('personal_access_tokens'))->pluck('name');

    expect($indexNames)->toContain('pat_tokenable_idx', 'pat_token_unique', 'pat_expires_idx');
    $indexNames->each(fn (string $name) => expect(strlen($name))->toBeLessThanOrEqual(64));
});

test('cross-user profile access follows role and ownership permissions', function () {
    $limited = profilePermissionGroup(['view_status' => true]);
    $limitedUser = profileUser($limited);
    $target = profileUser($limited);

    $this->actingAs($limitedUser)
        ->get(route('userProfile.show', $target))
        ->assertForbidden();

    $managerGroup = profilePermissionGroup(['roles_status' => true, 'view_status' => true]);
    $manager = profileUser($managerGroup);
    $ownedTarget = profileUser($limited, ['created_by' => $manager->id]);
    $otherTarget = profileUser($limited);

    $this->actingAs($manager)->get(route('userProfile.show', $ownedTarget))->assertOk();
    $this->actingAs($manager)->get(route('userProfile.show', $otherTarget))->assertForbidden();
});

test('profile updates preserve unrelated and privileged user fields', function () {
    $limited = profilePermissionGroup(['view_status' => true]);
    $user = profileUser($limited, [
        'connect_email' => 'inbox@example.test',
        'connect_password' => 'mail-secret',
        'access_token' => 'social-secret',
        'table_columns' => '{"sec_1":["title"]}',
    ]);

    $this->actingAs($user)->patch(route('userProfile.update', $user), [
        'name' => '<b>Updated Name</b>',
        'email' => 'updated@example.test',
        'permissions_id' => 999,
        'status' => 0,
        'connect_email' => null,
    ])->assertRedirect(route('userProfile.show', $user));

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('updated@example.test')
        ->and($user->permissions_id)->toBe($limited->id)
        ->and((bool) $user->status)->toBeTrue()
        ->and($user->connect_email)->toBe('inbox@example.test')
        ->and($user->connect_password)->toBe('mail-secret')
        ->and($user->access_token)->toBe('social-secret')
        ->and($user->table_columns)->toBe('{"sec_1":["title"]}');
});

test('password changes require the acting users password and confirmation', function () {
    $limited = profilePermissionGroup();
    $user = profileUser($limited);

    $this->actingAs($user)->patch(route('userProfile.password.update', $user), [
        'current_password' => 'wrong-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertSessionHasErrors('current_password', null, 'password');

    $this->actingAs($user)->patch(route('userProfile.password.update', $user), [
        'current_password' => 'password123',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('userProfile.show', $user));

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('only a webmaster may change another users password or account settings', function () {
    $managerGroup = profilePermissionGroup(['roles_status' => true]);
    $webmasterGroup = profilePermissionGroup(['roles_status' => true, 'webmaster_status' => true]);
    $limited = profilePermissionGroup();
    $replacementGroup = profilePermissionGroup();
    $manager = profileUser($managerGroup);
    $webmaster = profileUser($webmasterGroup);
    $target = profileUser($limited);

    $this->actingAs($manager)->patch(route('userProfile.password.update', $target), [
        'current_password' => 'password123',
        'password' => 'managed-password',
        'password_confirmation' => 'managed-password',
    ])->assertForbidden();

    $this->actingAs($manager)->patch(route('userProfile.account.update', $target), [
        'permissions_id' => $replacementGroup->id,
        'status' => 0,
    ])->assertForbidden();

    $this->actingAs($webmaster)->patch(route('userProfile.password.update', $target), [
        'current_password' => 'password123',
        'password' => 'managed-password',
        'password_confirmation' => 'managed-password',
    ])->assertRedirect(route('userProfile.show', $target));

    $this->actingAs($webmaster)->patch(route('userProfile.account.update', $target), [
        'permissions_id' => $replacementGroup->id,
        'status' => 0,
    ])->assertRedirect(route('userProfile.show', $target));

    $target->refresh();
    expect(Hash::check('managed-password', $target->password))->toBeTrue()
        ->and($target->permissions_id)->toBe($replacementGroup->id)
        ->and((bool) $target->status)->toBeFalse();
});

test('MCP tokens are multiple hashed scoped and revealed only on creation', function () {
    $limited = profilePermissionGroup();
    $user = profileUser($limited);

    $first = $this->actingAs($user)->postJson(route('userProfile.mcp.tokens.store', $user), [
        'name' => 'Claude Desktop',
    ])->assertCreated()->assertHeader('Cache-Control', 'no-store, private');

    $plainToken = $first->json('token');
    [$tokenId, $secret] = explode('|', $plainToken, 2);
    $storedToken = PersonalAccessToken::findOrFail($tokenId);

    expect($storedToken->token)->toBe(hash('sha256', $secret))
        ->and($storedToken->token)->not->toBe($plainToken)
        ->and($storedToken->abilities)->toBe(['mcp:use']);

    $this->actingAs($user)->postJson(route('userProfile.mcp.tokens.store', $user), [
        'name' => 'ChatGPT',
    ])->assertCreated();

    $this->actingAs($user)->patch(route('userProfile.mcp.tokens.update', [$user, $storedToken->id]), [
        'name' => 'Claude Desktop Updated',
    ])->assertRedirect(route('userProfile.show', $user));

    expect($user->mcpTokens()->count())->toBe(2)
        ->and($storedToken->fresh()->name)->toBe('Claude Desktop Updated');
    $this->actingAs($user)->get(route('userProfile.show', $user))
        ->assertOk()
        ->assertDontSee($plainToken);
});

test('disabling MCP preserves tokens and scoped revocation cannot affect other tokens', function () {
    $limited = profilePermissionGroup();
    $user = profileUser($limited);
    $mcpToken = $user->createToken('MCP', ['mcp:use'])->accessToken;
    $otherToken = $user->createToken('Other API', ['other:use'])->accessToken;

    $this->actingAs($user)->patch(route('userProfile.mcp.status.update', $user), [
        'current_password' => 'password123',
        'mcp_enabled' => 1,
    ])->assertRedirect(route('userProfile.show', $user));

    expect($user->fresh()->mcp_enabled)->toBeTrue();

    $this->actingAs($user)->patch(route('userProfile.mcp.status.update', $user), [
        'current_password' => 'password123',
        'mcp_enabled' => 0,
    ])->assertRedirect(route('userProfile.show', $user));

    expect($user->fresh()->mcp_enabled)->toBeFalse()
        ->and(PersonalAccessToken::count())->toBe(2);

    expect($user->mcpTokens()->whereKey($otherToken->id)->exists())->toBeFalse();

    $this->actingAs($user)->patchJson(route('userProfile.mcp.tokens.update', [$user, $otherToken->id]), [
        'name' => 'Should Not Change',
    ])->assertNotFound();

    $this->actingAs($user)->delete(route('userProfile.mcp.tokens.destroy', [$user, $mcpToken->id]))
        ->assertRedirect(route('userProfile.show', $user));

    expect(PersonalAccessToken::find($mcpToken->id))->toBeNull()
        ->and(PersonalAccessToken::find($otherToken->id)?->name)->toBe('Other API');
});

test('individual user deletion removes personal access tokens', function () {
    $webmasterGroup = profilePermissionGroup(['roles_status' => true, 'webmaster_status' => true]);
    $limited = profilePermissionGroup();
    $webmaster = profileUser($webmasterGroup);
    $target = profileUser($limited);
    $token = $target->createToken('MCP', ['mcp:use'])->accessToken;

    $this->actingAs($webmaster)
        ->withHeader('Referer', route('users'))
        ->get(route('usersDestroy', $target->id))
        ->assertRedirect();

    expect(User::find($target->id))->toBeNull()
        ->and(PersonalAccessToken::find($token->id))->toBeNull();
});

test('bulk and permission group deletion remove personal access tokens', function () {
    $webmasterGroup = profilePermissionGroup(['roles_status' => true, 'webmaster_status' => true]);
    $bulkGroup = profilePermissionGroup();
    $permissionGroup = profilePermissionGroup();
    $webmaster = profileUser($webmasterGroup);
    $bulkTarget = profileUser($bulkGroup);
    $permissionTarget = profileUser($permissionGroup);
    $bulkToken = $bulkTarget->createToken('Bulk MCP', ['mcp:use'])->accessToken;
    $permissionToken = $permissionTarget->createToken('Permission MCP', ['mcp:use'])->accessToken;

    $this->actingAs($webmaster)->post(route('usersUpdateAll'), [
        'ids' => [$bulkTarget->id],
        'action' => 'delete',
    ])->assertRedirect();

    expect(User::find($bulkTarget->id))->toBeNull()
        ->and(PersonalAccessToken::find($bulkToken->id))->toBeNull();

    $this->actingAs($webmaster)
        ->withHeader('Referer', route('users'))
        ->get(route('permissionsDestroy', $permissionGroup->id))
        ->assertRedirect();

    expect(User::find($permissionTarget->id))->toBeNull()
        ->and(PersonalAccessToken::find($permissionToken->id))->toBeNull();
});

test('guests are redirected to login from profile pages', function () {
    $limited = profilePermissionGroup();
    $user = profileUser($limited);

    $this->get(route('userProfile.show', $user))->assertRedirect(route('login'));
});

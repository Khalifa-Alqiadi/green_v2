<?php

use App\Models\Permissions;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Psr\Log\LoggerInterface;

uses(RefreshDatabase::class);

if (! defined('SMARTEND_MCP_MYSQL_TESTS')) {
    throw new RuntimeException('Run MCP tests with phpunit.mcp.xml; the guarded MySQL bootstrap is required.');
}

function mcpSetup($test): void
{
    config(['app.timezone' => 'Africa/Cairo', 'app.url' => 'https://configured.example',
        'smartend.default_language' => 'en', 'smartend.theme' => 'classic',
        'smartend_mcp.rate_limit' => 1000, 'smartend_mcp.allowed_origins' => []]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-06 12:00:00', 'Africa/Cairo'));
    DB::table('webmaster_settings')->insert(['id' => 1, 'version' => '13',
        'analytics_status' => 1, 'inbox_status' => 1, 'calendar_status' => 1, 'api_status' => 1, 'api_key' => 'public-api-test-key']);
    DB::table('settings')->insert(['id' => 1, 'site_status' => 1, 'site_title_en' => 'MCP Test Site']);
    $group = new Permissions;
    $group->forceFill(['name' => 'MCP test group', 'status' => 1, 'view_status' => 1,
        'analytics_status' => 1, 'inbox_status' => 1, 'calendar_status' => 1])->save();
    $test->mcpUser = new User;
    $test->mcpUser->forceFill(['name' => 'MCP user', 'email' => 'mcp@example.test', 'password' => 'password',
        'status' => 1, 'mcp_enabled' => true, 'permissions_id' => $group->id])->save();
    $test->mcpToken = $test->mcpUser->createToken('MCP testing', ['mcp:use'])->plainTextToken;
    $test->audit = [];
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')->andReturnUsing(function ($message, $context) use ($test) {
        $test->audit[] = $context;
    });
    Log::shouldReceive('channel')->with('mcp')->andReturn($logger);
}

function mcpRequest($test, string $method, array $params = [], ?string $token = null)
{
    // A new HTTP request must not inherit the previous request's cached auth guard.
    Auth::forgetGuards();

    return $test->withHeader('Authorization', 'Bearer '.($token ?? $test->mcpToken))
        ->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params]);
}

function mcpCall($test, string $name, mixed $arguments = null)
{
    return mcpRequest($test, 'tools/call', ['name' => $name, 'arguments' => $arguments ?? (object) []]);
}

beforeEach(fn () => mcpSetup($this));
afterEach(fn () => CarbonImmutable::setTestNow());

test('native initialization and discovery expose only permitted annotated V1 tools', function () {
    mcpRequest($this, 'initialize', ['capabilities' => (object) [], 'clientInfo' => ['name' => 'test', 'version' => '1']])
        ->assertOk()->assertJsonPath('result.serverInfo.name', 'Smartend CMS')
        ->assertJsonPath('result.capabilities.tools.listChanged', false)
        ->assertJsonMissingPath('result.capabilities.resources')->assertJsonMissingPath('result.capabilities.prompts');
    $response = mcpRequest($this, 'tools/list')->assertOk();
    $tools = $response->json('result.tools');
    expect(array_column($tools, 'name'))->toBe([
        'get_website_status', 'get_today_visitor_statistics', 'get_unread_site_mail', 'get_today_calendar_appointments',
    ]);
    foreach ($tools as $tool) {
        expect($tool['inputSchema']['additionalProperties'])->toBeFalse()
            ->and($tool['outputSchema']['required'])->toBe(['schema_version', 'data', 'meta'])
            ->and($tool['annotations'])->toMatchArray(['readOnlyHint' => true, 'destructiveHint' => false,
                'openWorldHint' => false, 'idempotentHint' => true]);
    }
    $this->mcpUser->permissionsGroup->forceFill(['inbox_status' => 0, 'calendar_status' => 0])->save();
    expect(mcpRequest($this, 'tools/list')->json('result.tools'))->toHaveCount(2);
    mcpCall($this, 'get_unread_site_mail')->assertStatus(403)->assertJsonPath('result.isError', true);
});

test('uses real hashed Sanctum tokens and normal last used updates', function () {
    $token = PersonalAccessToken::findToken($this->mcpToken);
    expect($token->token)->not->toBe($this->mcpToken)->and($token->last_used_at)->toBeNull();
    mcpCall($this, 'get_website_status')->assertOk()->assertJsonPath('result.isError', false);
    expect($token->fresh()->last_used_at)->not->toBeNull();
});

test('rejects absent malformed invalid revoked and expired credentials', function () {
    foreach ([null, 'Basic token', 'Bearer ', 'Bearer invalid token', 'Bearer invalid'] as $header) {
        Auth::forgetGuards();
        $this->flushHeaders();
        if ($header !== null) {
            $this->withHeader('Authorization', $header);
        }
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertStatus(401)->assertHeader('Cache-Control', 'no-store, private');
    }
    $token = PersonalAccessToken::findToken($this->mcpToken);
    $token->forceFill(['expires_at' => now()->subDay()])->save();
    mcpRequest($this, 'tools/list')->assertStatus(401);
    $token->forceFill(['expires_at' => null, 'created_at' => now()->subDays(2)])->save();
    config(['sanctum.expiration' => 60]);
    mcpRequest($this, 'tools/list')->assertStatus(401);
    config(['sanctum.expiration' => null]);
    $token->delete();
    mcpRequest($this, 'tools/list')->assertStatus(401);
});

test('rejects inactive disabled or insufficient ability users', function () {
    $wrong = $this->mcpUser->createToken('wrong ability', ['other'])->plainTextToken;
    mcpRequest($this, 'tools/list', [], $wrong)->assertStatus(403);
    $this->mcpUser->forceFill(['status' => 0])->save();
    mcpRequest($this, 'tools/list')->assertStatus(403);
    $this->mcpUser->forceFill(['status' => 1, 'mcp_enabled' => false])->save();
    mcpRequest($this, 'tools/list')->assertStatus(403);
});

test('sessions cannot substitute for bearer credentials even with a bogus header', function () {
    $this->actingAs($this->mcpUser, 'web');
    $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertStatus(401);
    $this->withHeader('Authorization', 'Bearer bogus')->postJson('/mcp', [
        'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list',
    ])->assertStatus(401);
});

test('origin allowlist is normalized and exact and responses are not cached', function () {
    config(['smartend_mcp.allowed_origins' => ['https://CLIENT.example:443', 'http://localhost:3000']]);
    foreach (['https://client.example', 'http://localhost:3000'] as $origin) {
        $this->withHeader('Origin', $origin);
        mcpRequest($this, 'tools/list')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }
    foreach (['null', 'https://client.example.evil', 'https://client.example/path', 'https://client.example:444',
        'https://user@client.example', 'https://client.example?x=1'] as $origin) {
        $this->withHeader('Origin', $origin);
        mcpRequest($this, 'tools/list')->assertStatus(403);
    }
});

test('one named limiter counts unauthenticated requests and body size is bounded', function () {
    config(['smartend_mcp.rate_limit' => 2]);
    mcpRequest($this, 'tools/list', [], 'invalid')->assertStatus(401);
    mcpRequest($this, 'tools/list', [], 'invalid')->assertStatus(401);
    mcpRequest($this, 'tools/list', [], 'invalid')->assertStatus(429);
    mcpRequest($this, 'tools/list')->assertOk();
    mcpRequest($this, 'tools/list')->assertOk();
    mcpRequest($this, 'tools/list')->assertStatus(429);
    config(['smartend_mcp.max_request_bytes' => 20]);
    mcpRequest($this, 'tools/list')->assertStatus(413);
});

test('malformed JSON and unknown tool names remain native protocol errors', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->mcpToken)->call('POST', '/mcp', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$this->mcpToken,
    ], '{')->assertOk()->assertJsonPath('error.code', -32700);
    mcpCall($this, 'unapproved_write_tool')->assertOk()->assertJsonPath('error.code', -32602);
});

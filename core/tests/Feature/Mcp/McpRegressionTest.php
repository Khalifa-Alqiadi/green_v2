<?php

use App\Http\Controllers\Dashboard\EventsController;
use App\Http\Controllers\Dashboard\WebmailsController;
use App\Models\AnalyticsVisitor;
use App\Models\Event;
use App\Models\Webmail;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);
if (! defined('SMARTEND_MCP_MYSQL_TESTS')) {
    throw new RuntimeException('Run MCP tests with phpunit.mcp.xml; the guarded MySQL bootstrap is required.');
}
beforeEach(fn () => mcpSetup($this));
afterEach(fn () => CarbonImmutable::setTestNow());

function mcpBusinessSnapshot(): array
{
    $snapshot = [];
    $prefix = DB::connection()->getTablePrefix();
    foreach (DB::select('SHOW TABLES') as $table) {
        $name = array_values((array) $table)[0];
        $logicalName = $prefix !== '' && str_starts_with($name, $prefix)
            ? substr($name, strlen($prefix))
            : $name;
        // Native Sanctum owns these operational timestamps; assert its other fields too.
        $rows = DB::table($logicalName)->get()->map(function ($row) use ($logicalName) {
            $row = (array) $row;
            if ($logicalName === 'personal_access_tokens') {
                unset($row['last_used_at'], $row['updated_at']);
            }

            return json_encode($row);
        })->all();
        sort($rows);
        $snapshot[$name] = $rows;
    }

    return $snapshot;
}

test('every tool preserves business tables and emits only redacted audit data', function () {
    mcpRecord(Webmail::class, ['created_by' => $this->mcpUser->id, 'title' => 'SECRET SUBJECT', 'details' => 'SECRET BODY']);
    mcpRecord(Event::class, ['created_by' => $this->mcpUser->id, 'user_id' => $this->mcpUser->id,
        'title' => 'SECRET EVENT', 'details' => 'SECRET SUMMARY', 'start_date' => '2026-09-06 00:00:00']);
    Http::preventStrayRequests();
    Http::fake();
    Mail::fake();
    Notification::fake();
    Queue::fake();
    $before = mcpBusinessSnapshot();
    foreach (['get_website_status', 'get_today_visitor_statistics', 'get_unread_site_mail', 'get_today_calendar_appointments'] as $tool) {
        mcpCall($this, $tool)->assertJsonPath('result.isError', false);
        expect(mcpBusinessSnapshot())->toBe($before);
        mcpCall($this, $tool, (object) ['secret' => 'SECRET ARGUMENT'])->assertJsonPath('result.isError', true);
        expect(mcpBusinessSnapshot())->toBe($before);
    }
    Mail::assertNothingSent();
    Mail::assertNothingQueued();
    Notification::assertNothingSent();
    Queue::assertNothingPushed();
    Http::assertNothingSent();
    expect($this->audit)->toHaveCount(8);
    foreach ($this->audit as $entry) {
        expect(array_diff(array_keys($entry), ['user_id', 'tool', 'timestamp', 'ip', 'outcome', 'error_code', 'limit', 'returned_count']))->toBe([]);
    }
    expect(json_encode($this->audit))->not->toContain('SECRET')->not->toContain($this->mcpToken);
});

test('existing mail and calendar read paths retain matching visibility', function () {
    $mail = mcpRecord(Webmail::class, ['created_by' => $this->mcpUser->id]);
    mcpRecord(Webmail::class, ['created_by' => null]);
    $event = mcpRecord(Event::class, ['created_by' => $this->mcpUser->id, 'user_id' => $this->mcpUser->id,
        'start_date' => '2026-09-06 00:00:00']);
    mcpRecord(Event::class, ['created_by' => null, 'user_id' => $this->mcpUser->id, 'start_date' => '2026-09-06 00:00:00']);
    $this->actingAs($this->mcpUser);
    expect(Helper::webmailsAlerts()->pluck('id')->all())->toBe([$mail->id]);
    expect(app(WebmailsController::class)->index('wait')->getData()['Webmails']->pluck('id')->all())->toBe([$mail->id]);
    expect(app(EventsController::class)->index()->getData()['Events']->pluck('id')->all())->toBe([$event->id]);
    mcpCall($this, 'get_unread_site_mail')->assertJsonPath('result.structuredContent.data.items.0.id', $mail->id);
    mcpCall($this, 'get_today_calendar_appointments')->assertJsonPath('result.structuredContent.data.items.0.id', $event->id);
});

test('public API keeps its independent token header authentication', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->mcpToken)->getJson('/api/v1/website/status')->assertStatus(401);
    $this->withHeader('token', 'public-api-test-key')->getJson('/api/v1/website/status')->assertOk();
});

test('maintenance exception allows only the authenticated MCP endpoint', function () {
    $maintenance = Mockery::mock(MaintenanceMode::class);
    $maintenance->shouldReceive('active')->andReturn(true);
    $maintenance->shouldReceive('data')->andReturn(['time' => time(), 'retry' => 60]);
    $this->app->instance(MaintenanceMode::class, $maintenance);
    $this->getJson('/api/v1/website/status')->assertStatus(503);
    $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertStatus(401);
    mcpCall($this, 'get_website_status')->assertJsonPath('result.structuredContent.data.maintenance_mode', true);
});

test('globally disabled features are hidden and direct calls return unavailable', function () {
    DB::table('webmaster_settings')->where('id', 1)->update(['analytics_status' => 0, 'inbox_status' => 0, 'calendar_status' => 0]);
    expect(mcpRequest($this, 'tools/list')->json('result.tools'))->toHaveCount(1);
    foreach (['get_today_visitor_statistics', 'get_unread_site_mail', 'get_today_calendar_appointments'] as $tool) {
        mcpCall($this, $tool)->assertOk()->assertJsonPath('result.isError', true)->assertSee('UNAVAILABLE');
    }
});

test('unexpected read errors never expose raw exceptions even in debug mode', function () {
    config(['app.debug' => true]);
    // Throw before the read query without altering schema or records.
    AnalyticsVisitor::addGlobalScope('mcp_failure_test', function () {
        throw new RuntimeException('SECRET SQL TOKEN /internal/path');
    });
    try {
        mcpCall($this, 'get_today_visitor_statistics')->assertOk()->assertJsonPath('result.isError', true)
            ->assertSee('INTERNAL_ERROR')->assertDontSee('SECRET')->assertDontSee('/internal/path');
    } finally {
        AnalyticsVisitor::clearBootedModels();
    }
});

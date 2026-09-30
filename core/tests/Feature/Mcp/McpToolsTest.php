<?php

use App\Mcp\Servers\SmartendDashboardServer;
use App\Mcp\Tools\GetWebsiteStatus;
use App\Models\AnalyticsPage;
use App\Models\AnalyticsVisitor;
use App\Models\Event;
use App\Models\User;
use App\Models\Webmail;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);
if (! defined('SMARTEND_MCP_MYSQL_TESTS')) {
    throw new RuntimeException('Run MCP tests with phpunit.mcp.xml; the guarded MySQL bootstrap is required.');
}
beforeEach(fn () => mcpSetup($this));
afterEach(fn () => CarbonImmutable::setTestNow());

function mcpRecord(string $class, array $fields)
{
    $model = new $class;
    $model->forceFill($fields)->save();

    return $model;
}

test('website returns configured safe identity through native MCP testing helpers', function () {
    $this->mcpUser->withAccessToken(PersonalAccessToken::findToken($this->mcpToken));
    SmartendDashboardServer::actingAs($this->mcpUser)->tool(GetWebsiteStatus::class)
        ->assertOk()->assertStructuredContent(fn ($json) => $json
        ->where('schema_version', 1)->where('data.website_status', 'open')
        ->where('data.maintenance_mode', false)->where('data.title', 'MCP Test Site')
        ->where('data.url', 'https://configured.example')->where('meta.date', '2026-09-06')
        ->where('meta.timezone', 'Africa/Cairo')->etc());
});

test('all tools return stable empty results and strict inputs', function () {
    foreach (['get_website_status', 'get_today_visitor_statistics', 'get_unread_site_mail', 'get_today_calendar_appointments'] as $tool) {
        $response = mcpCall($this, $tool)->assertOk()->assertJsonPath('result.isError', false);
        expect($response->json('result.structuredContent'))->toHaveKeys(['schema_version', 'data', 'meta']);
        expect(json_decode($response->json('result.content.0.text'), true))->toBe($response->json('result.structuredContent'));
        mcpCall($this, $tool, (object) ['user_id' => 999])->assertJsonPath('result.isError', true);
        mcpCall($this, $tool, [])->assertJsonPath('result.isError', true);
    }
    foreach ([0, 51, '5', 1.5, true, null] as $limit) {
        mcpCall($this, 'get_unread_site_mail', (object) ['limit' => $limit])->assertJsonPath('result.isError', true);
    }
    mcpCall($this, 'get_unread_site_mail')->assertJsonPath('result.structuredContent.data', [
        'items' => [], 'total_unread' => 0, 'returned_count' => 0, 'limit' => 5, 'has_more' => false,
    ]);
    mcpCall($this, 'get_today_visitor_statistics')->assertJsonPath('result.structuredContent.data', [
        'total_visitors' => 0, 'page_views' => 0, 'by_country' => [],
    ]);
    mcpCall($this, 'get_today_calendar_appointments')->assertJsonPath('result.structuredContent.data', ['items' => [], 'count' => 0]);
});

test('today statistics preserve dashboard record counts and related country pages', function () {
    $a = mcpRecord(AnalyticsVisitor::class, ['date' => '2026-09-06', 'ip' => '127.0.0.2', 'country' => 'Egypt', 'country_code' => 'EG']);
    $b = mcpRecord(AnalyticsVisitor::class, ['date' => '2026-09-06', 'ip' => '127.0.0.2', 'country' => 'Egypt', 'country_code' => 'EG']);
    $old = mcpRecord(AnalyticsVisitor::class, ['date' => '2026-09-05', 'country' => 'France']);
    mcpRecord(AnalyticsPage::class, ['visitor_id' => $a->id, 'date' => '2026-09-06', 'query' => '/one']);
    mcpRecord(AnalyticsPage::class, ['visitor_id' => $b->id, 'date' => '2026-09-06', 'query' => '/two']);
    mcpRecord(AnalyticsPage::class, ['visitor_id' => $old->id, 'date' => '2026-09-05']);
    // Existing dashboard country pages count the relation, even for an inconsistent old page date.
    mcpRecord(AnalyticsPage::class, ['visitor_id' => $a->id, 'date' => '2026-09-05']);
    mcpCall($this, 'get_today_visitor_statistics')->assertJsonPath('result.structuredContent.data', [
        'total_visitors' => 2, 'page_views' => 2,
        'by_country' => [['name' => 'Egypt', 'code' => 'EG', 'visitors' => 2, 'pages' => 3]],
    ]);
});

test('unread mail applies visibility before counts and limits and exposes only safe previews', function () {
    $other = mcpRecord(User::class, ['name' => 'Other', 'email' => 'other@example.test', 'password' => 'secret', 'permissions_id' => $this->mcpUser->permissions_id]);
    $first = mcpRecord(Webmail::class, ['created_by' => $this->mcpUser->id, 'date' => '2026-09-06 01:00:00', 'title' => null]);
    $last = mcpRecord(Webmail::class, ['created_by' => $this->mcpUser->id, 'details' => '<b>'.str_repeat('س', 400).'</b>',
        'to_bcc' => 'private@example.test', 'from_phone' => 'private-phone']);
    mcpRecord(Webmail::class, ['created_by' => $other->id, 'title' => 'private other message']);
    mcpRecord(Webmail::class, ['created_by' => $this->mcpUser->id, 'status' => 1]);
    mcpRecord(Webmail::class, ['created_by' => $this->mcpUser->id, 'cat_id' => 2]);
    $response = mcpCall($this, 'get_unread_site_mail', (object) ['limit' => 1])->assertOk()
        ->assertJsonPath('result.structuredContent.data.total_unread', 2)
        ->assertJsonPath('result.structuredContent.data.items.0.id', $last->id)
        ->assertJsonPath('result.structuredContent.data.has_more', true)
        ->assertDontSee('private@example.test')->assertDontSee('private-phone')->assertDontSee('private other message');
    expect(mb_strlen($response->json('result.structuredContent.data.items.0.preview')))->toBe(300);
    mcpCall($this, 'get_unread_site_mail')->assertJsonPath('result.structuredContent.data.items.1.subject', null)
        ->assertJsonPath('result.structuredContent.data.items.1.sender.email', null)
        ->assertJsonPath('result.structuredContent.data.items.1.date', '2026-09-06T01:00:00+03:00');
    expect((int) $first->fresh()->status)->toBe(0)->and((int) $last->fresh()->status)->toBe(0);
    $this->mcpUser->permissionsGroup->forceFill(['view_status' => 0])->save();
    mcpCall($this, 'get_unread_site_mail')->assertJsonPath('result.structuredContent.data.total_unread', 3);
});

test('calendar uses overlap exclusive ends local timestamps and existing event type behavior', function () {
    $base = ['user_id' => $this->mcpUser->id, 'created_by' => $this->mcpUser->id, 'title' => 'entry'];
    $note = mcpRecord(Event::class, $base + ['type' => 0, 'start_date' => '2026-09-06 12:00:00']);
    $meeting = mcpRecord(Event::class, $base + ['type' => 1, 'start_date' => '2026-09-05 23:30:00']);
    $range = mcpRecord(Event::class, $base + ['type' => 2, 'start_date' => '2026-09-04 12:00:00', 'end_date' => '2026-09-06 01:00:00']);
    $task = mcpRecord(Event::class, $base + ['type' => 3, 'start_date' => '2026-09-05 00:00:00', 'end_date' => '2026-09-07 00:00:00']);
    $fallback = mcpRecord(Event::class, $base + ['type' => 2, 'start_date' => '2026-09-06 02:00:00', 'end_date' => '2026-09-06 01:00:00']);
    mcpRecord(Event::class, $base + ['type' => 3, 'start_date' => '2026-09-04 00:00:00', 'end_date' => '2026-09-06 00:00:00']);
    mcpRecord(Event::class, $base + ['type' => 1, 'start_date' => '2026-09-07 00:00:00']);
    mcpRecord(Event::class, $base + ['type' => 0, 'start_date' => null]);
    mcpRecord(Event::class, array_replace($base, ['created_by' => null, 'type' => 0, 'start_date' => '2026-09-06 00:00:00']));
    $items = mcpCall($this, 'get_today_calendar_appointments')->assertJsonPath('result.structuredContent.data.count', 5)
        ->json('result.structuredContent.data.items');
    expect(array_column($items, 'id'))->toBe([$range->id, $task->id, $meeting->id, $note->id, $fallback->id])
        ->and($items[1]['end'])->toBe('2026-09-07')->and($items[2]['end'])->toBeNull()
        ->and($items[2]['start'])->toBe('2026-09-05T23:30:00+03:00')->and($items[3]['all_day'])->toBeTrue();
    $this->mcpUser->permissionsGroup->forceFill(['view_status' => 0])->save();
    mcpCall($this, 'get_today_calendar_appointments')->assertJsonPath('result.structuredContent.data.count', 6);
});

test('calendar day boundaries follow DST rather than fixed twenty four hour windows', function () {
    config(['app.timezone' => 'America/New_York']);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-08 12:00:00', 'America/New_York'));
    foreach (['2026-03-08 00:00:00', '2026-03-08 23:30:00', '2026-03-09 00:00:00'] as $start) {
        mcpRecord(Event::class, ['user_id' => $this->mcpUser->id, 'created_by' => $this->mcpUser->id, 'type' => 1, 'start_date' => $start]);
    }
    $items = mcpCall($this, 'get_today_calendar_appointments')->assertJsonPath('result.structuredContent.data.count', 2)
        ->json('result.structuredContent.data.items');
    expect($items[0]['start'])->toBe('2026-03-08T00:00:00-05:00')
        ->and($items[1]['start'])->toBe('2026-03-08T23:30:00-04:00');
});

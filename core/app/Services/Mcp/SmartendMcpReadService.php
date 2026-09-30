<?php

namespace App\Services\Mcp;

use App\Models\AnalyticsPage;
use App\Models\AnalyticsVisitor;
use App\Models\Event;
use App\Models\User;
use App\Models\Webmail;
use App\Models\WebmasterSetting;
use Carbon\CarbonImmutable;
use Helper;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Throwable;

class SmartendMcpReadService
{
    private const FLAGS = [
        'get_website_status' => null,
        'get_today_visitor_statistics' => 'analytics_status',
        'get_unread_site_mail' => 'inbox_status',
        'get_today_calendar_appointments' => 'calendar_status',
    ];

    public function shouldRegister(string $tool, ?User $user): bool
    {
        // v0.9.4 also filters tools/call through shouldRegister. Keep direct calls
        // resolvable so handle can return a native UNAVAILABLE/FORBIDDEN error.
        if (request()->json('method') !== 'tools/list') {
            return true;
        }

        try {
            return $this->accessError($tool, $user) === null;
        } catch (Throwable) {
            return false;
        }
    }

    private function accessError(string $tool, ?User $user): ?string
    {
        if (! $user || (int) $user->status !== 1 || ! $user->mcp_enabled || ! $user->tokenCan('mcp:use')) {
            return 'FORBIDDEN';
        }
        $flag = self::FLAGS[$tool];
        if ($flag === null) {
            return null;
        }
        // The helper's static cache can outlive a request in long-running workers.
        // Read the same settings row directly for security-sensitive availability.
        if (! WebmasterSetting::whereKey(1)->value($flag)) {
            return 'UNAVAILABLE';
        }

        return $user->permissionsGroup?->{$flag} ? null : 'FORBIDDEN';
    }

    public function run(string $tool, Request $request): Response|ResponseFactory
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $user = $request->user();
        $error = null;
        $data = null;
        $limit = null;

        try {
            $error = $this->accessError($tool, $user instanceof User ? $user : null);
            if ($error === null && ! $this->validArguments($tool, $request)) {
                $error = 'INVALID_ARGUMENTS';
            }
            if ($error === null) {
                $limit = $tool === 'get_unread_site_mail' ? $request->get('limit', 5) : null;
                $data = match ($tool) {
                    'get_website_status' => $this->websiteStatus(),
                    'get_today_visitor_statistics' => $this->statistics($now),
                    'get_unread_site_mail' => $this->mail($user, $limit),
                    'get_today_calendar_appointments' => $this->calendar($user, $now),
                };
            }
        } catch (Throwable) {
            $error = 'INTERNAL_ERROR';
        }

        if ($error === 'FORBIDDEN') {
            request()->attributes->set('mcp.forbidden', true);
        }

        // Explicit allowlist: never pass request/response objects or exceptions to logs.
        try {
            Log::channel('mcp')->info('mcp.tool', array_filter([
                'user_id' => $user instanceof User ? $user->id : null,
                'tool' => $tool,
                'timestamp' => $now->toIso8601String(),
                'ip' => request()->ip(),
                'outcome' => $error === null ? 'success' : 'failure',
                'error_code' => $error,
                'limit' => $limit,
                'returned_count' => $data['returned_count'] ?? $data['count'] ?? null,
            ], fn ($value) => $value !== null));
        } catch (Throwable) {
            // Fail closed if the required audit cannot be written, without leaking its exception.
            $error = 'INTERNAL_ERROR';
        }

        return $error !== null
            ? Response::error($error.': Unable to complete this read request.')
            : Response::structured([
                'schema_version' => 1,
                'data' => $data,
                'meta' => [
                    'date' => $now->toDateString(),
                    'timezone' => $now->timezoneName,
                    'generated_at' => $now->toIso8601String(),
                ],
            ]);
    }

    private function validArguments(string $tool, Request $request): bool
    {
        $raw = json_decode(request()->getContent());
        if (is_object($raw) && isset($raw->params) && is_object($raw->params)
            && property_exists($raw->params, 'arguments') && ! is_object($raw->params->arguments)) {
            return false;
        }
        $arguments = $request->all();
        if ($tool !== 'get_unread_site_mail') {
            return $arguments === [];
        }
        if (array_diff(array_keys($arguments), ['limit']) !== []) {
            return false;
        }

        return ! array_key_exists('limit', $arguments)
            || (is_int($arguments['limit']) && $arguments['limit'] >= 1 && $arguments['limit'] <= 50);
    }

    private function websiteStatus(): array
    {
        $status = Helper::GeneralSiteSettings('site_status');

        return [
            'website_status' => match ($status) {
                0, '0', false => 'closed',
                1, '1', true => 'open',
                default => 'unknown',
            },
            'maintenance_mode' => app()->isDownForMaintenance(),
            'title' => $this->plain(Helper::GeneralSiteSettings('site_title_'.config('smartend.default_language'))),
            'url' => (string) config('app.url'),
        ];
    }

    private function statistics(CarbonImmutable $now): array
    {
        $date = $now->toDateString();
        $visitors = AnalyticsVisitor::where('date', $date);
        $countries = (clone $visitors)->select('country')->selectRaw('COUNT(*) AS visitors, MAX(id) AS latest_id')
            ->groupBy('country')->orderByDesc('visitors')->orderBy('country')->get();
        $codes = AnalyticsVisitor::whereIn('id', $countries->pluck('latest_id'))->pluck('country_code', 'id');
        // Deliberately count related page rows, as the dashboard does, not a new metric.
        // Keep raw SQL free of table identifiers so DB_TABLE_PREFIX remains transparent.
        $visitorCountries = AnalyticsVisitor::where('date', $date)->pluck('country', 'id');
        $pageCounts = AnalyticsPage::whereIn('visitor_id', $visitorCountries->keys())
            ->select('visitor_id')->selectRaw('COUNT(*) AS pages')
            ->groupBy('visitor_id')->pluck('pages', 'visitor_id');

        return [
            'total_visitors' => (clone $visitors)->count(),
            'page_views' => AnalyticsPage::where('date', $date)->count(),
            'by_country' => $countries->map(fn ($country) => [
                'name' => $country->country,
                'code' => isset($codes[$country->latest_id]) ? substr($codes[$country->latest_id], 0, 2) : null,
                'visitors' => (int) $country->visitors,
                'pages' => (int) $visitorCountries
                    ->filter(fn ($visitorCountry) => $visitorCountry === $country->country)
                    ->keys()->sum(fn ($visitorId) => (int) ($pageCounts[$visitorId] ?? 0)),
            ])->all(),
        ];
    }

    private function mail(User $user, int $limit): array
    {
        $query = Webmail::where('status', 0)->where('cat_id', 0);
        if ($user->permissionsGroup->view_status) {
            $query->where('created_by', $user->id);
        }
        $total = (clone $query)->count();
        $items = $query->orderByDesc('id')->limit($limit)
            ->get(['id', 'title', 'from_name', 'from_email', 'to_name', 'to_email', 'date', 'details'])
            ->map(fn ($mail) => [
                'id' => (int) $mail->id,
                'subject' => $this->plain($mail->title),
                'sender' => ['name' => $this->plain($mail->from_name), 'email' => $mail->from_email],
                'recipient' => ['name' => $this->plain($mail->to_name), 'email' => $mail->to_email],
                'date' => $this->date($mail->date)?->toIso8601String(),
                'preview' => $this->plain($mail->details, 300),
            ])->all();

        return ['items' => $items, 'total_unread' => $total, 'returned_count' => count($items),
            'limit' => $limit, 'has_more' => $total > count($items)];
    }

    private function calendar(User $user, CarbonImmutable $now): array
    {
        $start = $now->startOfDay();
        $stop = $start->addDay();
        $query = Event::where('start_date', '<', $stop->format('Y-m-d H:i:s'))
            ->where(function ($query) use ($start) {
                $query->where('end_date', '>', $start->format('Y-m-d H:i:s'))
                    ->orWhere('start_date', '>=', $start->subDay()->format('Y-m-d H:i:s'));
            });
        if ($user->permissionsGroup->view_status) {
            $query->where('created_by', $user->id);
        }
        $items = [];
        foreach ($query->get(['id', 'type', 'title', 'details', 'start_date', 'end_date']) as $event) {
            $from = $this->date($event->start_date);
            if ($from === null) {
                continue;
            }
            $type = (int) $event->type;
            $allDay = ! in_array($type, [1, 2], true);
            $from = $allDay ? $from->startOfDay() : $from;
            $end = in_array($type, [2, 3], true) ? $this->date($event->end_date) : null;
            $end = $allDay ? $end?->startOfDay() : $end;
            if ($end !== null && $end <= $from) {
                $end = null;
            }
            $effectiveEnd = $end ?? ($allDay ? $from->addDay() : $from->addHour());
            if ($from >= $stop || $effectiveEnd <= $start) {
                continue;
            }
            $items[] = [
                '_sort' => $from->getTimestamp(),
                'id' => (int) $event->id,
                'type' => $type,
                'title' => $this->plain($event->title),
                'start' => $allDay ? $from->toDateString() : $from->toIso8601String(),
                'end' => $allDay ? $end?->toDateString() : $end?->toIso8601String(),
                'all_day' => $allDay,
                'summary' => $this->plain($event->details, 300),
            ];
        }
        usort($items, fn ($a, $b) => [$a['_sort'], $a['id']] <=> [$b['_sort'], $b['id']]);
        $items = array_map(function ($item) {
            unset($item['_sort']);

            return $item;
        }, $items);

        return ['items' => $items, 'count' => count($items)];
    }

    private function date(?string $value): ?CarbonImmutable
    {
        if ($value === null || preg_match('/\A\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}:\d{2})?\z/', $value) !== 1) {
            return null;
        }
        try {
            $format = strlen($value) === 10 ? 'Y-m-d' : 'Y-m-d H:i:s';
            $date = CarbonImmutable::createFromFormat('!'.$format, $value, config('app.timezone'));

            return $date && $date->format($format) === $value ? $date : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function plain(?string $value, ?int $limit = null): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = stripcslashes(strip_tags($value));

        return $limit === null ? $value : mb_substr($value, 0, $limit, 'UTF-8');
    }

    public static function envelopeSchema(JsonSchema $schema, array $data): array
    {
        return [
            'schema_version' => $schema->integer()->enum([1])->required(),
            'data' => $schema->object($data)->withoutAdditionalProperties()->required(),
            'meta' => $schema->object([
                'date' => $schema->string()->required(),
                'timezone' => $schema->string()->required(),
                'generated_at' => $schema->string()->required(),
            ])->withoutAdditionalProperties()->required(),
        ];
    }
}

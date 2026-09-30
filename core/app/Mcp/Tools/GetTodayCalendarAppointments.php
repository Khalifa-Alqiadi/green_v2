<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\Mcp\SmartendMcpReadService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_today_calendar_appointments')]
#[Title('Today’s calendar appointments')]
#[Description('Read permitted calendar entries occurring during today.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[IsIdempotent(true)]
class GetTodayCalendarAppointments extends Tool
{
    public function shouldRegister(SmartendMcpReadService $service): bool
    {
        $user = auth()->user();

        return $service->shouldRegister($this->name(), $user instanceof User ? $user : null);
    }

    public function handle(Request $request, SmartendMcpReadService $service): Response|ResponseFactory
    {
        return $service->run($this->name(), $request);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SmartendMcpReadService::envelopeSchema($schema, [
            'items' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->required(),
                'type' => $schema->integer()->required(),
                'title' => $schema->string()->nullable()->required(),
                'start' => $schema->string()->required(),
                'end' => $schema->string()->nullable()->required(),
                'all_day' => $schema->boolean()->required(),
                'summary' => $schema->string()->max(300)->nullable()->required(),
            ])->withoutAdditionalProperties())->required(),
            'count' => $schema->integer()->min(0)->required(),
        ]);
    }

    public function toArray(): array
    {
        $definition = parent::toArray();
        $definition['inputSchema']['additionalProperties'] = false;
        $definition['outputSchema']['additionalProperties'] = false;

        return $definition;
    }
}

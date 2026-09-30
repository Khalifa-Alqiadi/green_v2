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

#[Name('get_today_visitor_statistics')]
#[Title('Today’s visitor statistics')]
#[Description('Read today’s Smartend visitor/page metrics and country breakdown.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[IsIdempotent(true)]
class GetTodayVisitorStatistics extends Tool
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
            'total_visitors' => $schema->integer()->min(0)->required(),
            'page_views' => $schema->integer()->min(0)->required(),
            'by_country' => $schema->array()->items($schema->object([
                'name' => $schema->string()->nullable()->required(),
                'code' => $schema->string()->nullable()->required(),
                'visitors' => $schema->integer()->min(0)->required(),
                'pages' => $schema->integer()->min(0)->required(),
            ])->withoutAdditionalProperties())->required(),
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

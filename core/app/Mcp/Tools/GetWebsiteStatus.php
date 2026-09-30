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

#[Name('get_website_status')]
#[Title('Website status')]
#[Description('Read configured public availability, maintenance state and safe site identity.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[IsIdempotent(true)]
class GetWebsiteStatus extends Tool
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
            'website_status' => $schema->string()->enum(['open', 'closed', 'unknown'])->required(),
            'maintenance_mode' => $schema->boolean()->required(),
            'title' => $schema->string()->nullable()->required(),
            'url' => $schema->string()->required(),
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

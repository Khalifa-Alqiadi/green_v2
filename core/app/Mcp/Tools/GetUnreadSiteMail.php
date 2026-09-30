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

#[Name('get_unread_site_mail')]
#[Title('Unread website mail')]
#[Description('Read latest permitted unread inbox messages without changing read state.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[IsIdempotent(true)]
class GetUnreadSiteMail extends Tool
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
        return ['limit' => $schema->integer()->min(1)->max(50)->default(5)];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SmartendMcpReadService::envelopeSchema($schema, [
            'items' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->required(),
                'subject' => $schema->string()->nullable()->required(),
                'sender' => $schema->object([
                    'name' => $schema->string()->nullable()->required(),
                    'email' => $schema->string()->nullable()->required(),
                ])->withoutAdditionalProperties()->required(),
                'recipient' => $schema->object([
                    'name' => $schema->string()->nullable()->required(),
                    'email' => $schema->string()->nullable()->required(),
                ])->withoutAdditionalProperties()->required(),
                'date' => $schema->string()->nullable()->required(),
                'preview' => $schema->string()->max(300)->nullable()->required(),
            ])->withoutAdditionalProperties())->required(),
            'total_unread' => $schema->integer()->min(0)->required(),
            'returned_count' => $schema->integer()->min(0)->required(),
            'limit' => $schema->integer()->min(1)->max(50)->required(),
            'has_more' => $schema->boolean()->required(),
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

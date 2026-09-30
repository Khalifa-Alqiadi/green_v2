<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class TopicSeoAgent extends SmartendAgent implements HasStructuredOutput
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'seo_title' => $schema->string()->required(),
            'seo_description' => $schema->string()->required(),
            'keywords' => $schema->array()->items($schema->string())->required(),
            'slug' => $schema->string()->required(),
            'image_alt' => $schema->string()->required(),
        ];
    }
}

<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class TopicTranslatorAgent extends SmartendAgent implements HasStructuredOutput
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'segments' => $schema->array()->items(
                $schema->object([
                    'id' => $schema->string()->required(),
                    'text' => $schema->string()->required(),
                ])->withoutAdditionalProperties()
            )->required(),
        ];
    }
}

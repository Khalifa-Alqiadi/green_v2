<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class TopicImagePromptAgent extends SmartendAgent implements HasStructuredOutput
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'optimized_prompt' => $schema->string()->required(),
        ];
    }
}

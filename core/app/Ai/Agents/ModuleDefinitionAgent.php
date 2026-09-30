<?php

namespace App\Ai\Agents;

use App\Services\Dashboard\ModuleDefinitionCatalog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class ModuleDefinitionAgent extends SmartendAgent implements HasStructuredOutput
{
    public function schema(JsonSchema $schema): array
    {
        $translation = fn () => $schema->object([
            'language' => $schema->string()->required(),
            'value' => $schema->string()->required(),
        ])->withoutAdditionalProperties();

        $booleanProperties = fn (array $keys) => collect($keys)->mapWithKeys(
            fn ($key) => [$key => $schema->boolean()->required()]
        )->all();

        return [
            'can_create' => $schema->boolean()->required(),
            'titles' => $schema->array()->items($translation())->required(),
            'description' => $schema->string()->required(),
            'module_type' => $schema->integer()->enum(ModuleDefinitionCatalog::MODULE_TYPES)->required(),
            'sections_status' => $schema->boolean()->required(),
            'basic_fields' => $schema->object($booleanProperties(array_keys(ModuleDefinitionCatalog::BASIC_FIELDS)))->withoutAdditionalProperties()->required(),
            'additional_tabs' => $schema->object($booleanProperties(array_keys(ModuleDefinitionCatalog::ADDITIONAL_TABS)))->withoutAdditionalProperties()->required(),
            'custom_fields' => $schema->array()->items($schema->object([
                'labels' => $schema->array()->items($translation())->required(),
                'type' => $schema->integer()->enum(array_keys(ModuleDefinitionCatalog::CUSTOM_FIELD_TYPES))->required(),
                'required' => $schema->boolean()->required(),
                'default_value' => $schema->string()->nullable()->required(),
                'options' => $schema->array()->items($schema->object([
                    'labels' => $schema->array()->items($translation())->required(),
                    'color' => $schema->string()->nullable()->required(),
                ])->withoutAdditionalProperties())->required(),
                'fixed_texts' => $schema->array()->items($translation())->required(),
                'parent_module_id' => $schema->integer()->nullable()->required(),
                'in_table' => $schema->boolean()->required(),
                'in_search' => $schema->boolean()->required(),
                'in_listing' => $schema->boolean()->required(),
                'in_page' => $schema->boolean()->required(),
                'in_statics' => $schema->boolean()->required(),
            ])->withoutAdditionalProperties())->required(),
            'explanation' => $schema->array()->items($schema->string())->required(),
            'warnings' => $schema->array()->items($schema->string())->required(),
            'unsupported_requirements' => $schema->array()->items($schema->string())->required(),
        ];
    }
}

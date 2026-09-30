<?php

namespace App\Services\Ai;

use App\Models\Language;
use App\Models\Topic;
use App\Models\User;
use App\Models\WebmasterSection;
use App\Services\Dashboard\ModuleDefinitionCatalog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ModuleDefinitionValidator
{
    public function fromAgent(array $raw): array
    {
        $raw['titles'] = $this->translationMap($raw['titles'] ?? null);
        foreach ((array) ($raw['custom_fields'] ?? []) as $index => $field) {
            $raw['custom_fields'][$index]['labels'] = $this->translationMap($field['labels'] ?? null);
            $raw['custom_fields'][$index]['fixed_texts'] = $this->translationMap($field['fixed_texts'] ?? []);
            $raw['custom_fields'][$index]['options'] = $this->normalizeOptions($field['options'] ?? []);
        }

        return $raw;
    }

    public function validate(array $input, User $user, bool $rejectDuplicateTitles = true, ?array $allowedRelatedIds = null): array
    {
        $languages = $this->languages();
        $codes = $languages->pluck('code')->all();
        if ($codes === []) {
            throw ValidationException::withMessages(['titles' => __('backend.aiModuleLanguagesMissing')]);
        }

        $basicKeys = array_keys(ModuleDefinitionCatalog::BASIC_FIELDS);
        $tabKeys = array_keys(ModuleDefinitionCatalog::ADDITIONAL_TABS);
        $proposalKeys = ['titles', 'description', 'module_type', 'sections_status', 'basic_fields', 'additional_tabs', 'custom_fields', 'explanation', 'warnings', 'unsupported_requirements'];
        $fieldKeys = ['labels', 'type', 'required', 'default_value', 'options', 'fixed_texts', 'parent_module_id', 'in_table', 'in_search', 'in_listing', 'in_page', 'in_statics'];

        $rules = [
            'titles' => ['required', 'array:'.implode(',', $codes), 'size:'.count($codes)],
            'description' => ['required', 'string', 'max:2000'],
            'module_type' => ['required', 'integer', Rule::in(ModuleDefinitionCatalog::MODULE_TYPES)],
            'sections_status' => ['required', 'boolean'],
            'basic_fields' => ['required', 'array:'.implode(',', $basicKeys), 'size:'.count($basicKeys)],
            'additional_tabs' => ['required', 'array:'.implode(',', $tabKeys), 'size:'.count($tabKeys)],
            'custom_fields' => ['present', 'array', 'max:25'],
            'custom_fields.*' => ['array:'.implode(',', $fieldKeys)],
            'custom_fields.*.labels' => ['required', 'array:'.implode(',', $codes), 'size:'.count($codes)],
            'custom_fields.*.type' => ['required', 'integer', Rule::in(array_keys(ModuleDefinitionCatalog::CUSTOM_FIELD_TYPES))],
            'custom_fields.*.required' => ['required', 'boolean'],
            'custom_fields.*.default_value' => ['nullable', 'string', 'max:191'],
            'custom_fields.*.options' => ['present', 'array', 'list', 'max:50'],
            'custom_fields.*.options.*' => ['required', 'array:labels,color'],
            'custom_fields.*.options.*.labels' => ['required', 'array:'.implode(',', $codes), 'size:'.count($codes)],
            'custom_fields.*.options.*.color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'custom_fields.*.fixed_texts' => ['present', 'array'],
            'custom_fields.*.parent_module_id' => ['nullable', 'integer', 'min:1'],
            'custom_fields.*.in_table' => ['required', 'boolean'],
            'custom_fields.*.in_search' => ['required', 'boolean'],
            'custom_fields.*.in_listing' => ['required', 'boolean'],
            'custom_fields.*.in_page' => ['required', 'boolean'],
            'custom_fields.*.in_statics' => ['required', 'boolean'],
            'explanation' => ['present', 'array', 'max:20'],
            'explanation.*' => ['string', 'max:500'],
            'warnings' => ['present', 'array', 'max:20'],
            'warnings.*' => ['string', 'max:500'],
            'unsupported_requirements' => ['present', 'array', 'max:20'],
            'unsupported_requirements.*' => ['string', 'max:500'],
        ];
        foreach ($codes as $code) {
            $rules['titles.'.$code] = ['required', 'string', 'max:191'];
            $rules['custom_fields.*.labels.'.$code] = ['required', 'string', 'max:191'];
            $rules['custom_fields.*.options.*.labels.'.$code] = ['required', 'string', 'max:191', 'not_regex:/[\r\n]/'];
        }
        foreach ($basicKeys as $key) {
            $rules['basic_fields.'.$key] = ['required', 'boolean'];
        }
        foreach ($tabKeys as $key) {
            $rules['additional_tabs.'.$key] = ['required', 'boolean'];
        }

        $validator = Validator::make($input, $rules);
        $validator->after(function ($validator) use ($input, $codes, $user, $rejectDuplicateTitles, $proposalKeys, $allowedRelatedIds) {
            if (array_diff(array_keys($input), $proposalKeys) !== []) {
                $validator->errors()->add('proposal', __('validation.invalid'));
            }
            $this->validateSafeText($validator, $input);
            $this->validateFields($validator, $input, $codes, $user, $allowedRelatedIds);
            if ((int) ($input['module_type'] ?? -1) === 10 && count((array) ($input['custom_fields'] ?? [])) > 0) {
                $validator->errors()->add('custom_fields', __('backend.aiModuleLandingFieldsInvalid'));
            }
            if ($rejectDuplicateTitles) {
                foreach ($this->duplicateLanguages((array) ($input['titles'] ?? []), $user) as $code) {
                    $validator->errors()->add('titles.'.$code, __('backend.aiModuleDuplicateTitle'));
                }
            }
        });

        $validated = $validator->validate();
        $validated['module_type'] = (int) $validated['module_type'];
        $validated['sections_status'] = (bool) $validated['sections_status'];
        $validated['basic_fields'] = $this->booleans($validated['basic_fields']);
        $validated['additional_tabs'] = $this->booleans($validated['additional_tabs']);
        foreach ($validated['custom_fields'] as $index => $field) {
            $validated['custom_fields'][$index]['type'] = (int) $field['type'];
            $validated['custom_fields'][$index]['labels'] = $this->trimStrings($field['labels']);
            $validated['custom_fields'][$index]['options'] = array_map(fn ($option) => ['labels' => $this->trimStrings($option['labels']), 'color' => $option['color'] ?? null], $field['options']);
            $validated['custom_fields'][$index]['fixed_texts'] = $this->trimStrings($field['fixed_texts']);
            $validated['custom_fields'][$index]['default_value'] = filled($field['default_value'] ?? null) ? trim($field['default_value']) : null;
            foreach (['required', 'in_table', 'in_search', 'in_listing', 'in_page', 'in_statics'] as $key) {
                $validated['custom_fields'][$index][$key] = (bool) $field[$key];
            }
        }
        $validated['titles'] = $this->trimStrings($validated['titles']);
        $validated['description'] = trim($validated['description']);
        foreach (['explanation', 'warnings', 'unsupported_requirements'] as $key) {
            $validated[$key] = $this->trimStrings($validated[$key]);
        }

        return $validated;
    }

    public function visibleModules(User $user)
    {
        $query = WebmasterSection::query()->orderBy('row_no');
        if ($user->permissionsGroup?->view_status) {
            $query->where('created_by', $user->id);
        }

        return $query->get();
    }

    public function eligibleRelatedModules(User $user)
    {
        return $this->visibleModules($user)->where('status', 1)->values();
    }

    public function languages()
    {
        return Language::query()->where('status', 1)->where('box_status', 1)->orderBy('id')->get();
    }

    private function validateFields($validator, array $input, array $codes, User $user, ?array $allowedRelatedIds): void
    {
        $visibleIds = $this->eligibleRelatedModules($user)->modelKeys();
        if ($allowedRelatedIds !== null) {
            $visibleIds = array_intersect($visibleIds, $allowedRelatedIds);
        }
        foreach ((array) ($input['custom_fields'] ?? []) as $index => $field) {
            $type = (int) ($field['type'] ?? -1);
            $options = (array) ($field['options'] ?? []);
            $fixed = (array) ($field['fixed_texts'] ?? []);
            $parent = $field['parent_module_id'] ?? null;
            $default = $field['default_value'] ?? null;

            if (in_array($type, ModuleDefinitionCatalog::CHOICE_FIELD_TYPES, true)) {
                foreach ($options as $optionIndex => $option) {
                    $color = is_array($option) ? ($option['color'] ?? null) : null;
                    if ($color !== null && (! is_string($color) || preg_match('/^#[0-9a-fA-F]{6}$/D', $color) !== 1)) {
                        $validator->errors()->add("custom_fields.$index.options.$optionIndex.color", __('backend.aiModuleColorInvalid'));
                    }
                }
                if ($options === []) {
                    $validator->errors()->add("custom_fields.$index.options", __('backend.aiModuleOptionsInvalid'));
                }
                foreach ($codes as $code) {
                    $seen = [];
                    foreach ($options as $optionIndex => $option) {
                        $value = is_array($option) ? ($option['labels'][$code] ?? null) : null;
                        if (! is_string($value)) {
                            continue; // Structural rules report missing or malformed translations.
                        }
                        $normalized = Str::lower(trim($value));
                        if ($normalized === '' || in_array($normalized, $seen, true)) {
                            $validator->errors()->add("custom_fields.$index.options.$optionIndex.labels.$code", __('backend.aiModuleOptionsDuplicate'));
                        }
                        $seen[] = $normalized;
                    }
                }
            } elseif ($options !== []) {
                $validator->errors()->add("custom_fields.$index.options", __('backend.aiModuleOptionsNotAllowed'));
            }

            if ($type === 99) {
                if (count($fixed) !== count($codes) || array_diff(array_keys($fixed), $codes) !== [] || array_diff($codes, array_keys($fixed)) !== []) {
                    $validator->errors()->add("custom_fields.$index.fixed_texts", __('backend.aiModuleFixedLanguagesInvalid'));
                }
                foreach ($fixed as $code => $text) {
                    if (! is_string($text) || mb_strlen($text) > 2000) {
                        $validator->errors()->add("custom_fields.$index.fixed_texts.$code", __('validation.max.string', ['attribute' => 'fixed text', 'max' => 2000]));
                    }
                }
            } elseif ($fixed !== []) {
                $validator->errors()->add("custom_fields.$index.fixed_texts", __('backend.aiModuleFixedNotAllowed'));
            }

            if ($type === 18) {
                if (! $parent || ! in_array((int) $parent, array_map('intval', $visibleIds), true)) {
                    $validator->errors()->add("custom_fields.$index.parent_module_id", __('backend.aiModuleRelatedMissing'));
                } elseif ($default !== null && $default !== '' && ! Topic::query()->where('webmaster_id', $parent)->whereKey((int) $default)->exists()) {
                    $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleRelatedDefaultInvalid'));
                }
            } elseif ($parent !== null) {
                $validator->errors()->add("custom_fields.$index.parent_module_id", __('backend.aiModuleRelatedNotAllowed'));
            }

            if (in_array($type, [...ModuleDefinitionCatalog::FILE_FIELD_TYPES, ...ModuleDefinitionCatalog::MULTI_VALUE_FIELD_TYPES, 99], true) && filled($default)) {
                $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultNotAllowed'));
            }
            if (in_array($type, [6, 13], true) && filled($default)) {
                $count = count($options);
                if (! ctype_digit((string) $default) || (int) $default < 1 || (int) $default > $count) {
                    $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultInvalid'));
                }
            }
            if ($type === 14 && filled($default) && ! in_array((string) $default, ['0', '1'], true)) {
                $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultInvalid'));
            }
            if ($type === 2 && filled($default) && (! is_numeric($default) || (float) $default < 0)) {
                $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultInvalid'));
            }
            if ($type === 3 && filled($default) && filter_var($default, FILTER_VALIDATE_EMAIL) === false) {
                $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultInvalid'));
            }
            if ($type === 4 && filled($default) && ! $this->matchesDateFormat((string) $default, 'Y-m-d')) {
                $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultInvalid'));
            }
            if ($type === 5 && filled($default)
                && ! $this->matchesDateFormat((string) $default, 'Y-m-d H:i')
                && ! $this->matchesDateFormat((string) $default, 'Y-m-d H:i:s')) {
                $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultInvalid'));
            }
            if ($type === 15 && filled($default) && preg_match('/^\+?[0-9][0-9\s().-]{4,18}$/', (string) $default) !== 1) {
                $validator->errors()->add("custom_fields.$index.default_value", __('backend.aiModuleDefaultInvalid'));
            }
        }
    }

    private function validateSafeText($validator, array $input): void
    {
        $walk = function ($value, string $path) use (&$walk, $validator) {
            if (is_array($value)) {
                foreach ($value as $key => $nested) {
                    $walk($nested, $path === '' ? (string) $key : $path.'.'.$key);
                }
            } elseif (is_string($value) && $this->unsafe($value)) {
                $validator->errors()->add($path, __('backend.aiModuleUnsafeText'));
            }
        };
        $walk($input, '');
    }

    private function duplicateLanguages(array $titles, User $user): array
    {
        $modules = $this->visibleModules($user);
        $duplicates = [];
        foreach ($titles as $code => $title) {
            $normalized = Str::lower(trim(strip_tags((string) $title)));
            if ($normalized !== '' && $modules->contains(fn ($module) => Str::lower(trim((string) $module->{'title_'.$code})) === $normalized)) {
                $duplicates[] = $code;
            }
        }

        return $duplicates;
    }

    private function translationMap(mixed $translations): array
    {
        if (! is_array($translations)) {
            throw new RuntimeException('Malformed translation output.');
        }
        if (! array_is_list($translations)) {
            return $translations;
        }
        $result = [];
        foreach ($translations as $translation) {
            if (! is_array($translation) || array_diff(array_keys($translation), ['language', 'value']) !== [] || ! isset($translation['language'], $translation['value']) || ! is_string($translation['language']) || isset($result[$translation['language']])) {
                throw new RuntimeException('Malformed translation output.');
            }
            $result[(string) $translation['language']] = $translation['value'];
        }

        return $result;
    }

    private function normalizeOptions(mixed $options): array
    {
        if (! is_array($options) || ! array_is_list($options)) {
            throw new RuntimeException('Malformed options output.');
        }
        foreach ($options as $index => $option) {
            if (! is_array($option) || array_diff(array_keys($option), ['labels', 'color']) !== []) {
                throw new RuntimeException('Malformed options output.');
            }
            $options[$index]['labels'] = $this->translationMap($option['labels'] ?? null);
        }

        return $options;
    }

    private function booleans(array $values): array
    {
        return collect($values)->map(fn ($value) => (bool) $value)->all();
    }

    private function trimStrings(array $values): array
    {
        return collect($values)->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
    }

    private function unsafe(string $value): bool
    {
        return $value !== strip_tags($value)
            || str_contains($value, '```')
            || preg_match('/<\?(?:php|=)|#!\s*\/|javascript\s*:|\{\{|\{!!|@php|\bfunction\s*\(|\b(?:document|window)\s*\.|\b(?:SELECT\s+.+\s+FROM|INSERT\s+INTO|UPDATE\s+\w+\s+SET|DELETE\s+FROM|DROP\s+(?:TABLE|DATABASE)|ALTER\s+TABLE|CREATE\s+TABLE)\b|(?:^|\n)\s*(?:sudo\s+|rm\s+-|curl\s+|wget\s+|bash\s+-c|sh\s+-c)/is', $value) === 1;
    }

    private function matchesDateFormat(string $value, string $format): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);

        return $date !== false && $date->format($format) === $value;
    }
}

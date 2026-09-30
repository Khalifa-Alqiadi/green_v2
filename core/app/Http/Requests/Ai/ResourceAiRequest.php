<?php

namespace App\Http\Requests\Ai;

use App\Models\Banner;
use App\Models\Language;
use App\Models\Menu;
use App\Models\Popup;
use App\Models\Setting;
use App\Services\Ai\AiSettingsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

abstract class ResourceAiRequest extends FormRequest
{
    abstract protected function capability(): string;

    public function authorize(): bool
    {
        $type = (string) $this->input('resource_type');
        $group = $this->user()?->permissionsGroup;
        $settings = app(AiSettingsService::class);
        if (!$group || !$group->ai_use) {
            return false;
        }
        if (!$settings->capabilityEnabled($this->capability()) || !$settings->providerReady($settings->textProvider())) {
            return false;
        }

        $moduleFlag = ['setting' => 'settings_status', 'banner' => 'banners_status', 'popup' => 'popups_status', 'menu' => 'menus_status'][$type] ?? null;
        if (!$moduleFlag || !($group->{$moduleFlag} ?? false) || !\Helper::GeneralWebmasterSettings($moduleFlag)) {
            return false;
        }

        $resource = $this->resource();
        if ($this->filled('resource_id')) {
            if (!$resource || ($type !== 'setting' && !$group->edit_status)) {
                return false;
            }
            if (in_array($type, ['banner', 'popup'], true) && $group->view_status && (int) $resource->created_by !== (int) $this->user()->id) {
                return false;
            }
        } elseif ($type !== 'setting' && !$group->add_status) {
            return false;
        }

        if ($type === 'setting' && !$resource) {
            return false;
        }

        return $type !== 'banner' || $this->validBannerContext($resource);
    }

    public function resource(): ?Model
    {
        return once(function () {
            $id = $this->integer('resource_id');
            if (!$id) {
                return null;
            }

            return match ((string) $this->input('resource_type')) {
                'setting' => Setting::query()->find($id),
                'banner' => Banner::query()->find($id),
                'popup' => Popup::query()->find($id),
                'menu' => Menu::query()->find($id),
                default => null,
            };
        });
    }

    public function language(string $key = 'language'): ?Language
    {
        return Language::query()->where('status', 1)->where('box_status', 1)->where('code', $this->input($key))->first();
    }

    protected function baseRules(): array
    {
        return [
            'resource_type' => ['required', Rule::in(['setting', 'banner', 'popup', 'menu'])],
            'resource_id' => ['nullable', 'integer', 'min:1'],
            'context_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function activeLanguageRule(): mixed
    {
        return Rule::exists('languages', 'code')->where(fn ($query) => $query->where('status', 1)->where('box_status', 1));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => __('backend.aiUnavailable'),
            'code' => 'ai_unavailable_or_unauthorized',
        ], 403));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => __('validation.invalid'),
            'code' => 'validation_failed',
            'errors' => $validator->errors(),
        ], 422));
    }

    private function validBannerContext(?Model $resource): bool
    {
        $contextId = $this->integer('context_id');
        if ($resource instanceof Banner) {
            return !$contextId || (int) $resource->section_id === $contextId;
        }

        return $contextId > 0 && \App\Models\WebmasterBanner::query()->whereKey($contextId)->where('status', 1)->exists();
    }
}

<?php

namespace App\Services\Ai;

use App\Models\Topic;
use App\Models\User;
use App\Models\WebmasterSection;
use Illuminate\Database\Eloquent\Model;

class AiUiService
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiAccessService $access,
    ) {}

    public function capabilities(User $user, WebmasterSection $section, ?Topic $topic = null): array
    {
        $settings = $this->settings->settings();
        $textReady = $this->settings->providerReady($this->settings->textProvider($settings), $settings);
        $imageReady = $this->settings->providerReady($this->settings->imageProvider($settings), $settings);

        return [
            'content' => $textReady && $this->settings->capabilityEnabled('content', $settings)
                && $this->access->canUse($user, $section, $topic),
            'seo' => $textReady && $this->settings->capabilityEnabled('seo', $settings)
                && $this->access->canUse($user, $section, $topic),
            'translation' => $textReady && $this->settings->capabilityEnabled('translation', $settings)
                && $this->access->canUse($user, $section, $topic),
            'images' => $textReady && $imageReady && $this->settings->capabilityEnabled('images', $settings)
                && $this->access->canUse($user, $section, $topic),
            'alt' => $textReady && $this->settings->capabilityEnabled('content', $settings)
                && $this->access->canUse($user, $section, $topic),
        ];
    }

    public function resourceCapabilities(User $user, string $type, ?Model $resource = null): array
    {
        $settings = $this->settings->settings();
        $group = $user->permissionsGroup;
        $moduleFlag = ['setting' => 'settings_status', 'banner' => 'banners_status', 'popup' => 'popups_status', 'menu' => 'menus_status'][$type] ?? null;
        $moduleAllowed = $group && $moduleFlag && (bool) ($group->{$moduleFlag} ?? false)
            && (bool) \Helper::GeneralWebmasterSettings($moduleFlag);
        $actionAllowed = $type === 'setting'
            || ($resource ? (bool) ($group?->edit_status ?? false) : (bool) ($group?->add_status ?? false));
        $ownerAllowed = !($resource && in_array($type, ['banner', 'popup'], true) && ($group?->view_status ?? false))
            || (int) $resource->created_by === (int) $user->id;
        $textReady = $this->settings->providerReady($this->settings->textProvider($settings), $settings);
        $base = $moduleAllowed && $actionAllowed && $ownerAllowed && $textReady && (bool) ($group?->ai_use ?? false);

        return [
            'content' => $type !== 'setting' && $base && $this->settings->capabilityEnabled('content', $settings),
            'seo' => $type === 'setting' && $base && $this->settings->capabilityEnabled('seo', $settings),
            'translation' => $base && $this->settings->capabilityEnabled('translation', $settings),
            'images' => false,
            'alt' => false,
        ];
    }
}

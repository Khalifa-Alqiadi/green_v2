<?php

namespace App\Http\Middleware;

use Closure;
use Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $permissions = $user?->permissionsGroup;
        $routeName = (string) $request->route()?->getName();

        if ($routeName === 'NoPermission') {
            return $next($request);
        }

        if (!$user || !$permissions) {
            return redirect()->route('NoPermission');
        }

        if (!$this->hasModulePermission($routeName, $permissions)) {
            return redirect()->route('NoPermission');
        }

        if (!$this->hasSectionPermission($request, $permissions)) {
            return redirect()->route('NoPermission');
        }

        if (!$this->hasActionPermission($routeName, $permissions)) {
            return redirect()->route('NoPermission');
        }

        return $next($request);
    }

    private function hasModulePermission(string $routeName, object $permissions): bool
    {
        if ($routeName === 'usersEdit') {
            return true;
        }

        if ($routeName === 'webmasterSettings') {
            return $this->enabled($permissions, 'webmaster_status');
        }

        $checks = [
            'aiSettings' => ['webmaster_status'],
            'webmasterSettings' => ['webmaster_status'],
            'webmasterLanguage' => ['webmaster_status'],
            'webmasterSEORepair' => ['webmaster_status'],
            'mailSMTPCheck' => ['webmaster_status'],
            'mailTest' => ['webmaster_status'],
            'licenseCheck' => ['webmaster_status'],
            'languages' => ['webmaster_status'],
            'WebmasterSections' => ['modules_status'],
            'webmasterFields' => ['modules_status'],
            'settings' => ['settings_status', 'settings_status'],
            'users' => ['roles_status'],
            'permissions' => ['roles_status'],
            'customLinks' => ['roles_status'],
            'Banners' => ['banners_status', 'banners_status'],
            'WebmasterBanners' => ['banners_status', 'banners_status'],
            'tags' => ['tags_status', 'tags_status'],
            'authors' => ['authors_status', 'authors_status'],
            'popups' => ['popups_status', 'popups_status'],
            'menus' => ['menus_status', 'menus_status'],
            'parentMenus' => ['menus_status', 'menus_status'],
            'FileManager' => ['file_manager_status', 'file_manager_status'],
            'FilesManager' => ['file_manager_status', 'file_manager_status'],
            'fm.' => ['file_manager_status', 'file_manager_status'],
            'calendar' => ['calendar_status', 'calendar_status'],
            'webmails' => ['inbox_status', 'inbox_status'],
            'analytics' => ['analytics_status', 'analytics_status'],
            'visitors' => ['analytics_status', 'analytics_status'],
        ];

        foreach ($checks as $prefix => $check) {
            if (str_starts_with($routeName, $prefix)) {
                $permissionFlag = $check[0];
                $settingsFlag = $check[1] ?? null;

                return $this->enabled($permissions, $permissionFlag)
                    && ($settingsFlag === null || Helper::GeneralWebmasterSettings($settingsFlag));
            }
        }

        return true;
    }

    private function hasSectionPermission(Request $request, object $permissions): bool
    {
        $webmasterId = $request->route('webmasterId');
        if ($webmasterId === null || $webmasterId === '') {
            return true;
        }

        $allowedSections = array_filter(explode(',', (string) $permissions->data_sections));

        return in_array((string) $webmasterId, $allowedSections, true);
    }

    private function hasActionPermission(string $routeName, object $permissions): bool
    {
        if (!$this->routeUsesGeneralActionFlags($routeName)) {
            return true;
        }

        if (str_contains($routeName, 'Destroy') || str_contains($routeName, 'DestroyGroup')) {
            return $this->enabled($permissions, 'delete_status');
        }

        if (
            str_contains($routeName, 'Create')
            || str_contains($routeName, 'Store')
            || str_contains($routeName, 'Clone')
            || str_contains($routeName, 'Import')
            || str_contains($routeName, 'Upload')
        ) {
            return $this->enabled($permissions, 'add_status');
        }

        if (str_contains($routeName, 'Update') || str_contains($routeName, 'Edit') || str_contains($routeName, 'Save')) {
            return $this->enabled($permissions, 'edit_status');
        }

        return true;
    }

    private function routeUsesGeneralActionFlags(string $routeName): bool
    {
        foreach ([
            'Banners',
            'WebmasterBanners',
            'categories',
            'topics',
            'contacts',
            'webmails',
            'calendar',
            'menus',
            'parentMenus',
            'tags',
            'authors',
            'popups',
            'topicBlocks',
            'keditor',
        ] as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function enabled(object $permissions, string $flag): bool
    {
        return (bool) ($permissions->{$flag} ?? false);
    }
}

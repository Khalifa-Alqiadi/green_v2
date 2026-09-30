<?php

namespace App\Http\Controllers;

use App\Services\DemoThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DemoThemeController extends Controller
{
    public function __invoke(Request $request, string $theme): RedirectResponse
    {
        abort_unless(DemoThemeService::enabled($request), 404);
        abort_unless(in_array($theme, DemoThemeService::themes(), true), 404);

        $request->session()->put(DemoThemeService::SESSION_KEY, $theme);
        $request->session()->put('demo_theme_verify_return', true);
        $returnPath = $request->query('return', '/');
        if (! is_string($returnPath) || ! str_starts_with($returnPath, '/') || str_starts_with($returnPath, '//')) {
            $returnPath = '/';
        }

        return redirect($returnPath);
    }
}

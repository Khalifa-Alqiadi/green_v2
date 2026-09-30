@php
    $normalizeColor = static function ($value, string $fallback): string {
        $value = trim((string) $value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtoupper($value) : $fallback;
    };
    $relativeLuminance = static function (string $hex): float {
        $red = hexdec(substr($hex, 1, 2)) / 255;
        $green = hexdec(substr($hex, 3, 2)) / 255;
        $blue = hexdec(substr($hex, 5, 2)) / 255;
        $linearize = static fn (float $value): float => $value <= 0.03928
            ? $value / 12.92
            : (($value + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $linearize($red) + 0.7152 * $linearize($green) + 0.0722 * $linearize($blue);
    };
    $contrastColor = static function (string $hex) use ($relativeLuminance): string {
        $luminance = $relativeLuminance($hex);
        $darkContrast = ($luminance + 0.05) / 0.05;
        $lightContrast = 1.05 / ($luminance + 0.05);

        return $darkContrast >= $lightContrast ? '#0F172A' : '#FFFFFF';
    };

    $velocityBrand = $normalizeColor(Helper::GeneralSiteSettings('style_color1'), '#55C592');
    $velocityBrandStrong = $normalizeColor(Helper::GeneralSiteSettings('style_color2'), '#123F4C');
    $velocityMuted = $normalizeColor(Helper::GeneralSiteSettings('style_color3'), '#F4F8F5');
    $velocityBorder = $normalizeColor(Helper::GeneralSiteSettings('style_color4'), '#DCE8E3');
    $velocityBrandContrast = $contrastColor($velocityBrand);
    $velocityBrandStrongContrast = $contrastColor($velocityBrandStrong);
    $velocityDarkSurface = Helper::colorHexToDarken($velocityBrandStrong, 2.4);
    $velocityDarkSurfaceMuted = Helper::colorHexToDarken($velocityBrandStrong, 1.75);
    $velocityDarkSurfaceElevated = Helper::colorHexToDarken($velocityBrandStrong, 1.4);
    $velocityDarkSurfaceSoft = Helper::colorHexToDarken($velocityBrandStrong, 2);
@endphp

<style data-velocity-cms-tokens>
    :root[data-theme="velocity"] {
        --color-brand: {{ $velocityBrand }};
        --color-brand-strong: {{ $velocityBrandStrong }};
        --color-brand-contrast: {{ $velocityBrandContrast }};
        --color-brand-strong-contrast: {{ $velocityBrandStrongContrast }};
        --color-surface-muted: {{ $velocityMuted }};
        --color-border: {{ $velocityBorder }};
        --color-focus: color-mix(in srgb, {{ $velocityBrand }} 82%, white);
        --gradient-brand: linear-gradient(145deg, {{ $velocityBrandStrong }} 0%, color-mix(in srgb, {{ $velocityBrandStrong }} 70%, {{ $velocityBrand }}) 48%, {{ $velocityBrand }} 135%);
    }

    :root[data-theme="velocity"][data-appearance="dark"] {
        --color-surface: {{ $velocityDarkSurface }};
        --color-surface-muted: {{ $velocityDarkSurfaceMuted }};
        --color-surface-elevated: {{ $velocityDarkSurfaceElevated }};
        --color-surface-soft: {{ $velocityDarkSurfaceSoft }};
        --color-text: {{ $velocityMuted }};
        --color-text-muted: color-mix(in srgb, {{ $velocityBorder }} 78%, {{ $velocityBrandStrong }});
        --color-border: color-mix(in srgb, {{ $velocityBorder }} 22%, {{ $velocityDarkSurfaceMuted }});
        --color-control-surface: color-mix(in srgb, {{ $velocityBrandStrong }} 42%, {{ $velocityDarkSurface }});
        --color-control-surface-soft: color-mix(in srgb, {{ $velocityBrandStrong }} 24%, {{ $velocityDarkSurface }});
        --color-control-border: color-mix(in srgb, {{ $velocityBrand }} 46%, {{ $velocityDarkSurfaceElevated }});
        --gradient-soft: linear-gradient(145deg, color-mix(in srgb, {{ $velocityBrand }} 8%, {{ $velocityDarkSurface }}) 0%, {{ $velocityDarkSurfaceMuted }} 100%);
    }
</style>

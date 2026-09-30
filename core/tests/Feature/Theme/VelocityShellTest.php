<?php

use App\Services\ThemeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    config(['smartend.theme' => 'velocity']);
    ThemeService::flushRuntimeCache();
});

afterEach(function () {
    ThemeService::flushRuntimeCache();
});

test('velocity master renders its accessible isolated global shell', function () {
    $html = view('themes.velocity.layouts.master', [
        'PageTitle' => 'Velocity shell',
        'PageDescription' => 'Velocity shell description',
        'page_type' => 'home',
        'page_id' => 0,
    ])->render();

    expect($html)
        ->toContain('data-theme="velocity"')
        ->toContain('lang="en"')
        ->toContain('dir="ltr"')
        ->toContain('<title>Velocity shell</title>')
        ->toContain('content="Velocity shell description"')
        ->toContain('href="#main"')
        ->toContain('id="header"')
        ->toContain('id="topbar"')
        ->toContain('velocity-header__shell')
        ->toContain('data-velocity-menu-root')
        ->toContain('data-velocity-nav-back')
        ->toContain('id="footer"')
        ->toContain('data-velocity-cms-tokens')
        ->toContain('vendor/fontawesome/css/all.min.css')
        ->toContain('vendor/fontawesome/css/v4-shims.min.css')
        ->toContain('data-velocity-vendor-base')
        ->toContain('id="velocity-footer-subscribe-form"')
        ->toContain('data-velocity-component="cms-form-enhancer"')
        ->toContain('velocity-footer-subscribe-form-feedback')
        ->not->toMatch('/(?:bootstrap|jquery)/i');
});

test('velocity promotes the final main menu link to the desktop and mobile call to action', function () {
    $html = view('themes.velocity.layouts.master', [
        'PageTitle' => 'Velocity menu CTA',
        'page_type' => 'home',
        'page_id' => 0,
    ])->render();

    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);

    $desktopCta = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " velocity-header__cta ")]')->item(0);
    $mobileCta = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " velocity-nav__mobile-footer ")]/a')->item(0);
    $normalMenuLinks = $xpath->query('//*[@data-velocity-menu-root]/*[@data-velocity-menu-level="1"]/*[contains(concat(" ", normalize-space(@class), " "), " velocity-menu__row ")]/a');

    expect($desktopCta)->not->toBeNull()
        ->and($mobileCta)->not->toBeNull()
        ->and($desktopCta->getAttribute('href'))->toBe($mobileCta->getAttribute('href'));

    $ctaTitle = trim(str_replace('→', '', $desktopCta->textContent));
    $normalTitles = collect($normalMenuLinks)->map(fn (DOMNode $link): string => trim($link->textContent));

    expect($ctaTitle)->not->toBe('')
        ->and($normalTitles)->not->toContain($ctaTitle);
});

test('velocity submenus are hidden in the initial server rendered navigation', function () {
    $html = view('themes.velocity.layouts.master', [
        'PageTitle' => 'Velocity closed menus',
        'page_type' => 'home',
        'page_id' => 0,
    ])->render();

    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    $submenus = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " velocity-menu__submenu ")]');

    expect($submenus->length)->toBeGreaterThan(0);

    foreach ($submenus as $submenu) {
        expect($submenu->hasAttribute('hidden'))->toBeTrue();
    }

    $fallbackStyles = File::get(public_path('assets/velocity/css/fallback.css'));

    expect($fallbackStyles)
        ->toContain('html[data-theme="velocity"] .velocity-menu__submenu[hidden]')
        ->toContain('display: block !important;');
});

test('velocity submenu activity requires an exact URL and hover styling changes text only', function () {
    $menu = File::get(resource_path('views/themes/velocity/components/menu-items.blade.php'));
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($menu)
        ->toContain("\$activeClass = \$level === 1 ? \$sectionActiveClass : (\$isCurrentPage ? 'active' : '');")
        ->toContain('@if($isCurrentPage) aria-current="page" @endif')
        ->and($headerCss)
        ->toContain('.velocity-menu__submenu .velocity-menu__link:hover')
        ->toContain('color: var(--color-brand);')
        ->toContain('background: transparent;');
});

test('velocity header menu links use a smaller font in rtl layouts', function () {
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($headerCss)
        ->toContain('[data-theme="velocity"][dir="rtl"] .velocity-header .velocity-menu__link')
        ->toContain('font-size: clamp(0.75rem, 0.72rem + 0.08vw, 0.85rem);');
});

test('velocity desktop main menu uses an underline instead of active pill backgrounds', function () {
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($headerCss)
        ->toContain('.velocity-nav__list > .velocity-menu__item > .velocity-menu__row > .velocity-menu__link::after')
        ->toContain('background: var(--color-brand);')
        ->toContain('transform: scaleX(0);')
        ->toContain('transform: scaleX(1);');
});

test('velocity mobile menu hover and active links change text color without a background pill', function () {
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($headerCss)
        ->toContain('.velocity-nav__viewport .velocity-nav__list > .velocity-menu__item:hover > .velocity-menu__row > .velocity-menu__link,')
        ->toContain('.velocity-nav__viewport .velocity-menu__link:hover,')
        ->toContain('.velocity-nav__viewport .velocity-menu__link.active,')
        ->toContain('color: var(--color-brand);')
        ->toContain('background: transparent;')
        ->toContain('border-color: transparent;')
        ->toContain('box-shadow: none;');
});

test('velocity mobile submenu arrows match the rounded back button style', function () {
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($headerCss)
        ->toContain('.velocity-nav__viewport .velocity-menu__toggle,')
        ->toContain('width: 2.65rem;')
        ->toContain('height: 2.65rem;')
        ->toContain('color: var(--color-text);')
        ->toContain('background: var(--color-surface-muted);')
        ->toContain('border: 1px solid var(--color-border-soft);')
        ->toContain('border-radius: 50%;');
});

test('velocity mobile leaf links span the full menu row when no submenu arrow exists', function () {
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($headerCss)
        ->toContain('.velocity-nav__viewport .velocity-menu__row > .velocity-menu__link:only-child')
        ->toContain('grid-column: 1 / -1;');
});

test('velocity landing pages retain homepage top-level header colors without recoloring submenus', function () {
    $html = view('themes.velocity.layouts.master', [
        'PageTitle' => 'Velocity landing header',
        'WebmasterSection' => (object) ['type' => 10],
    ])->render();
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($html)
        ->toContain('velocity-page--landing')
        ->and($headerCss)
        ->toContain('.velocity-page--landing:not(.velocity-page--home) .velocity-header:not([data-scrolled="true"])')
        ->toContain('.velocity-nav__list > .velocity-menu__item > .velocity-menu__row > :is(.velocity-menu__link, .velocity-menu__toggle)')
        ->toContain('color: #ffffff;')
        ->not->toContain('.velocity-page--landing:not(.velocity-page--home) .velocity-header:not([data-scrolled="true"]) :is(.velocity-menu__link, .velocity-menu__toggle, .velocity-topbar .velocity-control)')
        ->toContain('body:not(.velocity-page--landing):not(:has(.velocity-hero, .velocity-page-heading))');
});

test('velocity landing page mobile drawers keep menu links readable on their light surface', function () {
    $headerCss = File::get(resource_path('themes/velocity/css/header.css'));

    expect($headerCss)
        ->toContain('@media (max-width: 64rem)')
        ->toContain('.velocity-nav__viewport .velocity-nav__list > .velocity-menu__item > .velocity-menu__row > :is(.velocity-menu__link, .velocity-menu__toggle)')
        ->toContain('color: var(--color-text);');
});

test('velocity shell honors hidden header and footer controls', function () {
    $html = view('themes.velocity.layouts.master', [
        'PageTitle' => 'Focused page',
        'HideHeader' => true,
        'HideFooter' => true,
    ])->render();

    expect($html)
        ->not->toContain('id="header"')
        ->not->toContain('id="footer"')
        ->toContain('id="main"');
});

test('velocity omits the footer back-to-top link and provides the WhatsApp chat widget contract', function () {
    $html = view('themes.velocity.layouts.master', [
        'PageTitle' => 'Velocity controls',
        'page_type' => 'home',
        'page_id' => 0,
    ])->render();
    $footer = File::get(resource_path('views/themes/velocity/layouts/footer.blade.php'));
    $foot = File::get(resource_path('views/themes/velocity/layouts/foot.blade.php'));
    $globalUi = File::get(resource_path('themes/velocity/js/modules/global-ui.js'));

    expect($html)->not->toContain('velocity-footer__top-link')
        ->and($html)->toContain('class="velocity-social"')
        ->and($html)->toContain('aria-disabled="true"')
        ->and($html)->not->toContain('data-velocity-back-to-top')
        ->and($foot)->not->toContain('velocity-back-to-top')
        ->and($footer)->toContain('data-velocity-whatsapp-panel')
        ->and($footer)->toContain('data-velocity-whatsapp-toggle')
        ->and($footer)->toContain('https://wa.me/{{ $velocityWhatsapp }}')
        ->and($globalUi)->toContain('setupWhatsAppWidgets');
});

test('velocity uses a filled brand glyph for its WhatsApp actions', function () {
    $html = view('themes.velocity.components.icon', ['name' => 'whatsapp'])->render();

    expect($html)
        ->toContain('velocity-icon--brand')
        ->toContain('viewBox="0 0 16 16"');
});

test('velocity footer promotes contact details into its top callout and retains the contact button', function () {
    $footer = File::get(resource_path('views/themes/velocity/layouts/footer.blade.php'));

    expect($footer)
        ->toContain('velocity-footer-cta__contact')
        ->toContain('href="tel:{{ $velocityContactPhone }}"')
        ->toContain('href="mailto:{{ $velocityContactEmail }}"')
        ->toContain('class="velocity-button velocity-button--primary" href="{{ $velocityContactUrl }}"')
        ->not->toContain('velocity-footer__contact-section')
        ->not->toContain("<h2>{{ __('frontend.getInTouch') }}</h2>");
});

test('velocity footer menu links use encoding-safe rtl-aware directional arrows', function () {
    $styles = File::get(resource_path('themes/velocity/css/components.css'));
    $fallbackStyles = File::get(public_path('assets/velocity/css/fallback.css'));

    expect($styles)
        ->toContain('.velocity-footer__list a::before')
        ->toContain('content: "";')
        ->toContain('border-right: 1.5px solid currentColor;')
        ->toContain('transform: rotate(-45deg);')
        ->toContain('[data-theme="velocity"][dir="rtl"] .velocity-footer__list a::before')
        ->toContain('border-left: 1.5px solid currentColor;')
        ->toContain('transform: rotate(45deg);')
        ->not->toContain('content: "\\203A";')
        ->toContain('.velocity-footer__list a:is(:hover, :focus-visible)')
        ->toContain('color: var(--footer-text);')
        ->toContain('font-weight: 700;')
        ->toContain('text-decoration: none;')
        ->and($fallbackStyles)
        ->toContain('.velocity-footer__list a::before')
        ->toContain('content: "";')
        ->toContain('[dir="rtl"] .velocity-footer__list a::before')
        ->not->toContain('content: "\\203A";');
});

test('velocity renders a complete rtl maintenance page', function () {
    session(['locale' => 'ar']);
    app()->setLocale('ar');

    $html = view('themes.velocity.closed', [
        'PageTitle' => 'Maintenance',
        'close_message' => '<h1>Temporarily unavailable</h1>',
    ])->render();

    expect($html)
        ->toContain('data-theme="velocity"')
        ->toContain('lang="ar"')
        ->toContain('dir="rtl"')
        ->toContain('velocity-maintenance')
        ->toContain('<h1>Temporarily unavailable</h1>');
});

test('velocity uses its static fallback when the compiled manifest is unavailable', function () {
    config(['smartend.compiled_themes.velocity.build_directory' => 'build/velocity-missing']);

    $html = view('themes.velocity.layouts.master', ['PageTitle' => 'Fallback'])->render();

    expect($html)
        ->toContain('data-theme-asset-fallback')
        ->toContain('/assets/velocity/css/fallback.css');
});

test('velocity pagination is semantic and independent from bootstrap', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 30, 10, 2, [
        'path' => 'https://example.test/articles',
    ]);

    $html = $paginator->links('themes.velocity.components.pagination')->render();

    expect($html)
        ->toContain('velocity-pagination')
        ->toContain('aria-current="page"')
        ->toContain('rel="prev"')
        ->toContain('rel="next"')
        ->not->toContain('page-item');
});

test('velocity global sources contain no jquery or globally loaded bootstrap framework', function () {
    $sourceFiles = [
        ...File::allFiles(resource_path('views/themes/velocity/layouts')),
        ...File::allFiles(resource_path('views/themes/velocity/components')),
        ...File::allFiles(resource_path('themes/velocity')),
    ];
    $source = collect($sourceFiles)
        ->map(fn (SplFileInfo $file): string => File::get($file->getPathname()))
        ->implode("\n");

    expect($source)
        ->not->toContain("vendor/bootstrap/css/bootstrap")
        ->not->toContain("vendor/bootstrap/js/bootstrap")
        ->not->toMatch('/\bjquery\b/i')
        ->toContain('prefers-reduced-motion')
        ->toContain('data-velocity-appearance-toggle')
        ->toContain('data-velocity-submenu-toggle');
});

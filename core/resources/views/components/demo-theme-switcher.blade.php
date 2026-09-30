@php
    $demoThemes = \App\Services\DemoThemeService::themes();
    $demoThemeCurrent = \App\Services\ThemeService::active();
    $demoThemeArabic = str_starts_with(strtolower((string) (Helper::currentLanguage()->code ?? app()->getLocale())), 'ar');
@endphp
@if(\App\Services\DemoThemeService::enabled(request()) && count($demoThemes) > 1)
<div class="demo-theme-switcher" data-demo-theme-switcher>
    <button class="demo-theme-switcher__toggle" type="button" aria-expanded="false" aria-controls="demo-theme-panel" aria-label="{{ $demoThemeArabic ? 'اختيار تصميم الموقع' : 'Choose website theme' }}">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 0 18h1.4a2.1 2.1 0 0 0 .5-4.14 1.05 1.05 0 0 1 .25-2.06c.77-.25 1.62-.38 2.48-.38H18A3 3 0 0 0 21 11.4 8.4 8.4 0 0 0 12 3Zm-5 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm2-4A1.5 1.5 0 1 1 9 5a1.5 1.5 0 0 1 0 3.5Zm6 0A1.5 1.5 0 1 1 15 5a1.5 1.5 0 0 1 0 3.5Zm2 4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/></svg>
    </button>
    <div class="demo-theme-switcher__panel" id="demo-theme-panel" role="dialog" aria-label="{{ $demoThemeArabic ? 'تصاميم الموقع' : 'Website themes' }}" hidden>
        <strong>{{ $demoThemeArabic ? 'اختر التصميم' : 'Choose a theme' }}</strong>
        <div class="demo-theme-switcher__options" role="list">
            @foreach($demoThemes as $demoTheme)
                <a href="{{ route('demoTheme.switch', ['theme' => $demoTheme, 'return' => '/'.ltrim(request()->getRequestUri(), '/')]) }}"
                   class="demo-theme-switcher__option {{ $demoThemeCurrent === $demoTheme ? 'is-current' : '' }}"
                   @if($demoThemeCurrent === $demoTheme) aria-current="true" @endif role="listitem">
                    <img src="{{ \App\Services\ThemeService::previewAsset($demoTheme) }}" alt="" width="80" height="48" loading="lazy">
                    <span>{{ ucfirst($demoTheme) }}</span>
                    @if($demoThemeCurrent === $demoTheme)<span class="demo-theme-switcher__check" aria-label="{{ $demoThemeArabic ? 'الحالي' : 'Current' }}">✓</span>@endif
                </a>
            @endforeach
        </div>
    </div>
</div>
<style>
.demo-theme-switcher{position:fixed;z-index:2147483000;inset-inline-start:max(1rem,env(safe-area-inset-left));bottom:max(1.5rem,env(safe-area-inset-bottom));font-family:system-ui,-apple-system,sans-serif;color:#172033;text-align:start}.demo-theme-switcher__toggle{display:grid;place-items:center;width:3rem;height:3rem;padding:0;border:1px solid rgba(255,255,255,.42);border-radius:50%;background:#172033;color:#fff;box-shadow:0 8px 28px rgba(15,23,42,.28);cursor:pointer}.demo-theme-switcher__toggle:hover{transform:translateY(-2px)}.demo-theme-switcher__toggle:focus-visible,.demo-theme-switcher__option:focus-visible{outline:3px solid #60a5fa;outline-offset:3px}.demo-theme-switcher__toggle svg{width:1.45rem;height:1.45rem;fill:currentColor}.demo-theme-switcher__panel{position:absolute;inset-inline-start:0;bottom:calc(100% + .75rem);width:min(18rem,calc(100vw - 2rem));padding:1rem;border:1px solid rgba(148,163,184,.35);border-radius:1rem;background:#fff;color:#172033;box-shadow:0 18px 48px rgba(15,23,42,.24)}.demo-theme-switcher__panel[hidden]{display:none}.demo-theme-switcher__options{display:grid;gap:.5rem;margin-top:.75rem}.demo-theme-switcher__option{display:grid;grid-template-columns:5rem 1fr auto;align-items:center;gap:.75rem;padding:.45rem;border:1px solid #e2e8f0;border-radius:.75rem;background:#fff;color:#172033;text-decoration:none!important}.demo-theme-switcher__option:hover{border-color:#94a3b8;background:#f8fafc;color:#172033}.demo-theme-switcher__option.is-current{border-color:#2563eb;background:#eff6ff}.demo-theme-switcher__option img{display:block;width:5rem;height:3rem;object-fit:cover;border-radius:.45rem}.demo-theme-switcher__check{display:grid;place-items:center;width:1.4rem;height:1.4rem;border-radius:50%;background:#2563eb;color:#fff;font-size:.8rem}@media(max-width:767px){.demo-theme-switcher{bottom:max(5.25rem,calc(env(safe-area-inset-bottom) + 4rem))}.demo-theme-switcher__toggle{width:2.75rem;height:2.75rem}}@media(prefers-reduced-motion:no-preference){.demo-theme-switcher__toggle{transition:transform .18s ease}.demo-theme-switcher__panel{animation:demo-theme-in .18s ease}@keyframes demo-theme-in{from{opacity:0;transform:translateY(.4rem)}}}
</style>
<script>
(()=>{const root=document.querySelector('[data-demo-theme-switcher]');if(!root)return;const button=root.querySelector('button');const panel=root.querySelector('[role="dialog"]');const links=[...panel.querySelectorAll('a')];const close=(focus=true)=>{panel.hidden=true;button.setAttribute('aria-expanded','false');if(focus)button.focus()};const open=()=>{panel.hidden=false;button.setAttribute('aria-expanded','true');(panel.querySelector('[aria-current="true"]')||links[0])?.focus()};button.addEventListener('click',()=>panel.hidden?open():close());document.addEventListener('keydown',e=>{if(panel.hidden)return;if(e.key==='Escape'){e.preventDefault();close()}if(['ArrowDown','ArrowUp','Home','End'].includes(e.key)){e.preventDefault();let i=links.indexOf(document.activeElement);if(e.key==='Home')i=0;else if(e.key==='End')i=links.length-1;else i=(i+(e.key==='ArrowDown'?1:-1)+links.length)%links.length;links[i].focus()}});document.addEventListener('click',e=>{if(!panel.hidden&&!root.contains(e.target))close(false)})})();
</script>
@endif

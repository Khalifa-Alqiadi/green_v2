@php
    $velocityCode = @Helper::currentLanguage()->code;
    $velocityClasses = collect(preg_split('/\s+/', (string) ($block->css_classes ?? ''), -1, PREG_SPLIT_NO_EMPTY))
        ->filter(fn ($class) => preg_match('/^[a-zA-Z0-9_-]+$/', $class))
        ->implode(' ');
    $velocityBackground = trim((string) ($block->bg_color ?? ''));
    $velocityStyles = [];

    if (preg_match('/^(?:#[0-9a-fA-F]{3,8}|(?:rgb|hsl)a?\([0-9.,%\s]+\)|[a-zA-Z]{3,20})$/', $velocityBackground)) {
        $velocityStyles[] = '--velocity-block-background: '.$velocityBackground;
    }

    $velocityBackgroundFile = (string) ($contents->{'bg_'.$velocityCode} ?? '');
    if (($block->image_status ?? false) && $velocityBackgroundFile !== '') {
        $velocityStyles[] = "--velocity-block-image: url('".route('fileView', ['path' => 'topics/'.$velocityBackgroundFile])."')";
    }

    $velocityTitle = (string) ($contents->{'title_'.$velocityCode} ?? '');
    $velocityDescription = (string) ($contents->{'desc_'.$velocityCode} ?? '');
@endphp
<section
    id="landing-block-{{ $block->id }}"
    class="velocity-section velocity-landing-block {{ ($block->divider_status ?? false) ? 'velocity-landing-block--divider' : '' }} {{ $velocityClasses }}"
    @if($velocityStyles !== []) style="{{ implode('; ', $velocityStyles) }}" @endif
    data-velocity-landing-block
    data-velocity-reveal
>
    <div class="{{ ($wide ?? false) ? 'velocity-container--wide' : 'velocity-container' }} {{ isset($actions) || isset($footer) ? 'velocity-section-layout' : '' }}">
        @include(theme_view('components.section-heading'), [
            'title' => ($block->title_status ?? false) ? $velocityTitle : '',
            'description' => ($block->desc_status ?? false) ? $velocityDescription : '',
        ])
        {{ $slot }}
        @isset($actions)
            <div class="velocity-section-actions {{ ($hasCarouselActions ?? false) ? 'velocity-section-actions--carousel' : '' }}">
                {{ $actions }}
            </div>
        @endisset
        @isset($footer)
            <div class="velocity-section-actions">
                {{ $footer }}
            </div>
        @endisset
    </div>
</section>

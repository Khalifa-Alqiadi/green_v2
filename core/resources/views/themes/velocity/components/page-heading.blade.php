@php
    $breadcrumbs = collect($breadcrumbs ?? [])->filter(fn ($item) => filled($item['label'] ?? null));
    $velocityHeadingVariant = in_array(($variant ?? 'gradient'), ['gradient', 'media', 'editorial', 'compact'], true)
        ? ($variant ?? 'gradient')
        : 'gradient';
    $velocityHeadingMedia = trim((string) ($media ?? ''));
@endphp
<header
    class="velocity-page-heading velocity-page-heading--{{ $velocityHeadingVariant }} {{ $velocityHeadingMedia !== '' ? 'velocity-page-heading--has-media' : '' }}"
    data-velocity-reveal
>
    @if($velocityHeadingMedia !== '')
        <img
            class="velocity-page-heading__media"
            src="{{ $velocityHeadingMedia }}"
            alt=""
            width="1920"
            height="640"
            loading="eager"
            decoding="async"
            fetchpriority="high"
            aria-hidden="true"
        >
    @endif
    <div class="velocity-container">
        <nav aria-label="{{ __('backend.home') }}">
            <ol class="velocity-breadcrumbs">
                <li><a href="{{ Helper::homeURL() }}">{{ __('backend.home') }}</a></li>
                @foreach($breadcrumbs as $breadcrumb)
                    <li>
                        @if(filled($breadcrumb['url'] ?? null) && !$loop->last)
                            <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                        @else
                            <span @if($loop->last) aria-current="page" @endif>{{ $breadcrumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
        <div class="velocity-page-heading__copy">
            <h1>{{ $title }}</h1>
            @if(filled($description ?? null))
                <div class="velocity-page-heading__description velocity-richtext">{!! nl2br($description) !!}</div>
            @endif
        </div>
    </div>
</header>

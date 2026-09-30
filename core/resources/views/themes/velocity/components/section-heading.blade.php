@php
    $headingTag = in_array($level ?? 2, [2, 3, 4], true) ? 'h'.($level ?? 2) : 'h2';
@endphp
@if(filled($title ?? null) || filled($description ?? null))
    <header class="velocity-section-heading {{ $align ?? '' }}" data-velocity-reveal>
        @if(filled($eyebrow ?? null))
            <p class="velocity-eyebrow velocity-section-heading__eyebrow">{{ $eyebrow }}</p>
        @endif
        @if(filled($title ?? null))
            <{{ $headingTag }} class="velocity-section-heading__title velocity-balanced">{{ $title }}</{{ $headingTag }}>
        @endif
        @if(filled($description ?? null))
            <p class="velocity-section-heading__description">{{ $description }}</p>
        @endif
    </header>
@endif

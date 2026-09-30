@php
    $title = $title ?? null;
    $actionUrl = $actionUrl ?? null;
    $actionLabel = $actionLabel ?? null;
    $slot = $slot ?? '';
@endphp
<section class="velocity-empty velocity-panel {{ $class ?? '' }}">
    <span class="velocity-empty__mark" aria-hidden="true">—</span>
    <h2>{{ $title ?: __('frontend.noData') }}</h2>
    @if(trim((string) $slot) !== '')
        <div class="velocity-muted">{{ $slot }}</div>
    @endif
    @if($actionUrl && $actionLabel)
        <a class="velocity-button velocity-button--primary" href="{{ $actionUrl }}">{{ $actionLabel }}</a>
    @endif
</section>

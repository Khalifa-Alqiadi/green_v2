@php
    $tone = $tone ?? 'warning';
    $dismissible = $dismissible ?? false;
@endphp
<div
    class="velocity-status velocity-status--{{ $tone }} {{ $class ?? '' }}"
    role="{{ $tone === 'error' ? 'alert' : 'status' }}"
    aria-live="{{ $tone === 'error' ? 'assertive' : 'polite' }}"
    @if($dismissible) data-velocity-dismissible @endif
>
    <div>{{ $slot }}</div>
    @if($dismissible)
        <button class="velocity-status__dismiss" type="button" data-velocity-dismiss aria-label="{{ __('backend.close') }}">
            @include(theme_view('components.icon'), ['name' => 'close'])
        </button>
    @endif
</div>

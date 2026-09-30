@if(filled($url ?? null))
    <div class="velocity-more">
        <a class="velocity-button velocity-button--secondary" href="{{ $url }}">
            {{ $label ?? __('frontend.viewMore') }}
            <span class="velocity-arrow" aria-hidden="true">→</span>
        </a>
    </div>
@endif

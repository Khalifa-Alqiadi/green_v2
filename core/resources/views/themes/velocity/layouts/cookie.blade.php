@if(Helper::GeneralWebmasterSettings('cookie_policy_status'))
    @php
        $velocityPolicy = Helper::Topic(3);
    @endphp
    <aside class="velocity-cookie" data-velocity-cookie aria-label="{{ __('frontend.cookiesAcceptBtn') }}">
        <p>
            {!! __('frontend.cookiesAcceptMessage') !!}
            @if($velocityPolicy)
                <a href="{{ Helper::topicURL(3) }}">
                    {{ $velocityPolicy->{'title_'.Helper::currentLanguage()->code} }}
                </a>
            @endif
        </p>
        <button class="velocity-button velocity-button--primary" type="button" data-velocity-cookie-accept>
            {{ __('frontend.cookiesAcceptBtn') }}
        </button>
    </aside>
@endif

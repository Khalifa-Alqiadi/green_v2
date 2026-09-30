<nav class="velocity-topic-share" aria-label="{{ __('frontend.share') }}">
    <div class="velocity-topic-share__heading">
        <span class="velocity-topic-share__icon" aria-hidden="true">
            @include(theme_view('components.icon'), ['name' => 'share'])
        </span>
        <strong>{{ __('frontend.share') }}</strong>
    </div>
    <ul class="velocity-topic-share__list">
        @foreach([
            'facebook' => ['label' => 'Facebook', 'icon' => 'facebook'],
            'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'whatsapp'],
            'twitter' => ['label' => 'X', 'icon' => 'x'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin'],
            'tumblr' => ['label' => 'Tumblr', 'icon' => 'tumblr'],
        ] as $velocityNetwork => $velocityShare)
            <li>
                <a
                    class="velocity-topic-share__link velocity-topic-share__link--{{ $velocityNetwork }}"
                    href="{{ Helper::SocialShare($velocityNetwork, $PageTitle ?? '') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    title="{{ $velocityShare['label'] }}"
                >
                    <span class="velocity-topic-share__network-icon" aria-hidden="true">
                        @include(theme_view('components.social-icon'), ['name' => $velocityShare['icon']])
                    </span>
                    <span>{{ $velocityShare['label'] }}</span>
                    <span class="velocity-visually-hidden">({{ __('frontend.share') }})</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>

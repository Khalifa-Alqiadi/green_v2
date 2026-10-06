<header class="yh-section-head meu-reveal">
    <div class="yh-section-head__copy">
        <span class="yh-section-head__eyebrow">{{ $yhEyebrow ?? __('frontend.latestNews') }}</span>
        @if($TopicBlock->title_status && $yhBlockTitle)
            <h2>{{ $yhBlockTitle }}</h2>
        @endif
        @if($TopicBlock->desc_status && $yhBlockDescription)
            <p>{{ $yhBlockDescription }}</p>
        @endif
    </div>
    @if($TopicBlock->more_btn_status)
        <a class="yh-section-head__more" href="{{ $yhSectionUrl }}">
            <span>{{ __('yemen_home.view_all') }}</span>
            <i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
        </a>
    @endif
</header>

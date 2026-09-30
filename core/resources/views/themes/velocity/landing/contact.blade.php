@component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
    <div class="velocity-contact-layout">
        <aside class="velocity-contact-details">
            @if(Helper::GeneralSiteSettings('contact_t1_'.@Helper::currentLanguage()->code))
                <div class="velocity-contact-detail">
                    <strong>{{ __('frontend.address') }}</strong>
                    <span>{{ Helper::GeneralSiteSettings('contact_t1_'.@Helper::currentLanguage()->code) }}</span>
                </div>
            @endif
            @if(Helper::GeneralSiteSettings('contact_t3') || Helper::GeneralSiteSettings('contact_t5'))
                <div class="velocity-contact-detail">
                    <strong>{{ __('frontend.callPhone') }}</strong>
                    @foreach(['contact_t3', 'contact_t5'] as $velocityPhoneSetting)
                        @if(Helper::GeneralSiteSettings($velocityPhoneSetting))
                            <a class="velocity-ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', Helper::GeneralSiteSettings($velocityPhoneSetting)) }}">
                                {{ Helper::GeneralSiteSettings($velocityPhoneSetting) }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
            @if(Helper::GeneralSiteSettings('contact_t6'))
                <div class="velocity-contact-detail">
                    <strong>{{ __('frontend.email') }}</strong>
                    <a href="mailto:{{ Helper::GeneralSiteSettings('contact_t6') }}">{{ Helper::GeneralSiteSettings('contact_t6') }}</a>
                </div>
            @endif
            @include(theme_view('layouts.social'), ['tt_position' => 'top'])
        </aside>

        @include(theme_view('components.contact-form'), [
            'formId' => 'velocity-landing-contact-form-'.$TopicBlock->id,
        ])
    </div>
@endcomponent

@if(!empty($Topic))
    @php
        $velocityDetailsField = filled($Topic->{$details_var} ?? null) ? $details_var : $details_var2;
        $velocityDetails = (string) ($Topic->{$velocityDetailsField} ?? '');
        $velocityForm = $Topic->form;
    @endphp
    @if(filled($velocityDetails))
        <section id="welcome" class="velocity-section velocity-welcome" data-velocity-reveal>
            <div class="velocity-container velocity-welcome__inner">
                <div class="velocity-rich-content">{!! $velocityDetails !!}</div>
                @if(!empty($velocityForm))
                    <a class="velocity-button velocity-button--primary" href="{{ Helper::sectionURL($velocityForm->id) }}">
                        {{ __('backend.submit') }} {{ $velocityForm->{'title_'.@Helper::currentLanguage()->code} }}
                        <span class="velocity-arrow" aria-hidden="true">→</span>
                    </a>
                @endif
            </div>
        </section>
    @endif

    @if(filled($Topic->css_code) || filled($Topic->js_code))
        @push('after-styles')
            @if(filled($Topic->css_code))
                <style data-velocity-topic-css>{!! $Topic->css_code !!}</style>
            @endif
            {!! $Topic->js_code !!}
        @endpush
    @endif
    @if(filled($Topic->body_code))
        @push('before-footer')
            {!! Blade::render($Topic->body_code) !!}
        @endpush
    @endif
@endif

@include(theme_view('layouts.banners'), [
    'BannersSettingsId' => Helper::GeneralWebmasterSettings('home_text_banners_section_id'),
])

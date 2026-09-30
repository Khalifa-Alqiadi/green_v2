@php
    $velocityFaqs = Helper::Topics(Helper::GeneralWebmasterSettings('home_content7_section_id'), 0, 10, 1);
    $velocitySectionUrl = $velocityFaqs->isNotEmpty() ? Helper::sectionURL($velocityFaqs->first()->webmaster_id) : null;
@endphp
@if($velocityFaqs->isNotEmpty())
    <section id="faq" class="velocity-section velocity-section--muted" data-velocity-reveal>
        <div class="velocity-container velocity-section-layout">
            @include(theme_view('components.section-heading'), [
                'title' => __('frontend.homeFAQTitle'),
                'description' => __('frontend.homeFAQDesc'),
            ])
            <div class="velocity-faq-grid">
                @foreach($velocityFaqs as $velocityFaq)
                    @php
                        $velocityFaqTitle = $velocityFaq->{$title_var} ?: $velocityFaq->{$title_var2};
                        $velocityFaqDetailsField = filled($velocityFaq->{$details_var}) ? $details_var : $details_var2;
                    @endphp
                    <details class="velocity-faq velocity-panel" data-velocity-disclosure data-velocity-reveal>
                        <summary>{{ $velocityFaqTitle }} <span aria-hidden="true">+</span></summary>
                        <div class="velocity-rich-content">{!! str_replace('"#', '"'.Request::url().'#', $velocityFaq->{$velocityFaqDetailsField}) !!}</div>
                    </details>
                @endforeach
            </div>
            <div class="velocity-section-actions">
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            </div>
        </div>
    </section>
@endif

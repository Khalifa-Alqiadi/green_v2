@php
    $velocityFaqs = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::Topics(
            $TopicBlockContents->module_id,
            $TopicBlockContents->category_ids ?? '',
            $TopicBlockContents->records_count ?? 10,
            0,
            $TopicBlockContents->records_order ?? 0
        )
        : collect();
    $velocityCode = @Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityFaqs->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
        <div class="velocity-faq-grid">
            @foreach($velocityFaqs as $velocityFaq)
                @php
                    $velocityTitle = $velocityFaq->{'title_'.$velocityCode} ?: $velocityFaq->{'title_'.$velocityDefaultCode};
                    $velocityDetails = $velocityFaq->{'details_'.$velocityCode} ?: $velocityFaq->{'details_'.$velocityDefaultCode};
                @endphp
                <details class="velocity-faq velocity-panel" data-velocity-disclosure data-velocity-reveal>
                    <summary>{{ $velocityTitle }} <span aria-hidden="true">+</span></summary>
                    <div class="velocity-rich-content">{!! str_replace('"#', '"'.Request::url().'#', $velocityDetails) !!}</div>
                </details>
            @endforeach
        </div>
        @if($TopicBlock->more_btn_status)
            @slot('actions')
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endslot
        @endif
    @endcomponent
@endif

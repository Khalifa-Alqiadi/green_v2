@php
    $velocityPartners = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::Topics(
            $TopicBlockContents->module_id,
            $TopicBlockContents->category_ids ?? '',
            $TopicBlockContents->records_count ?? 0,
            0,
            $TopicBlockContents->records_order ?? 0
        )
        : collect();
    $velocityCode = @Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityPartners->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
        <div class="velocity-partners-grid" role="list">
            @foreach($velocityPartners as $velocityPartner)
                @php
                    $velocityTitle = $velocityPartner->{'title_'.$velocityCode} ?: $velocityPartner->{'title_'.$velocityDefaultCode};
                    $velocityUrl = collect($velocityPartner->fields)->pluck('field_value')->first(fn ($value) => filter_var($value, FILTER_VALIDATE_URL));
                @endphp
                <div class="velocity-partners-grid__item" role="listitem">
                    @if($velocityUrl)
                        <a class="velocity-partners-grid__content" href="{{ $velocityUrl }}" target="_blank" rel="noopener noreferrer">
                    @else
                        <div class="velocity-partners-grid__content">
                    @endif
                        @if(filled($velocityPartner->photo_file))
                            <img src="{{ route('fileView', ['path' => 'topics/'.$velocityPartner->photo_file]) }}?w=320&h=180&r=fit" alt="{{ Helper::topicPhotoAlt($velocityPartner) }}" width="320" height="180" loading="lazy" decoding="async">
                        @else
                            <span>{{ $velocityTitle }}</span>
                        @endif
                    @if($velocityUrl)
                        </a>
                    @else
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if($TopicBlock->more_btn_status)
            @slot('actions')
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endslot
        @endif
    @endcomponent
@endif

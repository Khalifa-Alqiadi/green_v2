@php
    $velocityStaff = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::Topics(
            $TopicBlockContents->module_id,
            $TopicBlockContents->category_ids ?? '',
            $TopicBlockContents->records_count ?? 6,
            0,
            $TopicBlockContents->records_order ?? 0
        )
        : collect();
    $velocityCode = @Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityStaff->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
        <div class="velocity-people-grid">
            @foreach($velocityStaff as $velocityPerson)
                @php
                    $velocityTitle = $velocityPerson->{'title_'.$velocityCode} ?: $velocityPerson->{'title_'.$velocityDefaultCode};
                    $velocityDetails = $velocityPerson->{'details_'.$velocityCode} ?: $velocityPerson->{'details_'.$velocityDefaultCode};
                    $velocityUrl = Helper::topicURL($velocityPerson->id, '', $velocityPerson);
                @endphp
                <article class="velocity-person velocity-panel">
                    @if(filled($velocityPerson->photo_file))
                        <a href="{{ $velocityUrl }}" class="velocity-person__media" tabindex="-1" aria-hidden="true">
                            <img src="{{ route('fileView', ['path' => 'topics/'.$velocityPerson->photo_file]) }}?w=360&h=420&r=fit" alt="{{ Helper::topicPhotoAlt($velocityPerson) }}" width="360" height="420" loading="lazy" decoding="async">
                        </a>
                    @endif
                    <div class="velocity-person__body">
                        <h3><a href="{{ $velocityUrl }}">{{ $velocityTitle }}</a></h3>
                        @if(filled(strip_tags($velocityDetails)))
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($velocityDetails), 140) }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @if($TopicBlock->more_btn_status)
            @slot('actions')
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endslot
        @endif
    @endcomponent
@endif

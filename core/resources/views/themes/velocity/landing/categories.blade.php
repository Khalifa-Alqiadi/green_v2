@php
    $velocityCategories = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::SectionCategories(
            $TopicBlockContents->module_id,
            $TopicBlockContents->records_count ?? 0,
            $TopicBlockContents->category_ids ?? ''
        )
        : collect();
    $velocityCode = @Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityCategories->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
        <div class="velocity-category-grid">
            @foreach($velocityCategories as $velocityCategory)
                @php
                    $velocityTitle = $velocityCategory->{'title_'.$velocityCode} ?: $velocityCategory->{'title_'.$velocityDefaultCode};
                    $velocityDetails = $velocityCategory->{'details_'.$velocityCode} ?: $velocityCategory->{'details_'.$velocityDefaultCode};
                @endphp
                <a class="velocity-category-card velocity-panel" href="{{ Helper::categoryURL($velocityCategory->id, '', $velocityCategory) }}">
                    @if(filled($velocityCategory->photo))
                        <img src="{{ route('fileView', ['path' => 'sections/'.$velocityCategory->photo]) }}?w=320&h=240&r=fit" alt="" width="320" height="240" loading="lazy" decoding="async">
                    @elseif(filled($velocityCategory->icon))
                        <span class="velocity-category-card__icon" aria-hidden="true"><i class="{{ $velocityCategory->icon }}"></i></span>
                    @endif
                    <h3>{{ $velocityTitle }}</h3>
                    @if(filled($velocityDetails))
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($velocityDetails), 130) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
        @if($TopicBlock->more_btn_status)
            @slot('actions')
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endslot
        @endif
    @endcomponent
@endif

@php
    $velocityBlockTopics = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::Topics(
            $TopicBlockContents->module_id,
            $TopicBlockContents->category_ids ?? '',
            $TopicBlockContents->records_count ?? 12,
            0,
            $TopicBlockContents->records_order ?? 0
        )
        : collect();
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::sectionURL($TopicBlockContents->module_id)
        : null;
    $velocityCarouselId = 'velocity-landing-carousel-'.$TopicBlock->id;
@endphp
@if($velocityBlockTopics->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents, 'hasCarouselActions' => true])
        <div id="{{ $velocityCarouselId }}" class="velocity-carousel velocity-carousel--cards" data-velocity-carousel-track>
            @foreach($velocityBlockTopics as $velocityBlockTopic)
                <div class="velocity-carousel__item" data-velocity-carousel-slide>
                    @include(theme_view('components.topic-card'), ['topic' => $velocityBlockTopic])
                </div>
            @endforeach
        </div>
        @slot('actions')
            @include(theme_view('components.carousel-controls'), [
                'target' => $velocityCarouselId,
                'count' => $velocityBlockTopics->count(),
            ])
            @if($TopicBlock->more_btn_status)
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endif
        @endslot
    @endcomponent
@endif

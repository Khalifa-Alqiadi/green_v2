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
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityBlockTopics->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
        <div class="velocity-topic-grid">
            @foreach($velocityBlockTopics as $velocityBlockTopic)
                @include(theme_view('components.topic-card'), ['topic' => $velocityBlockTopic])
            @endforeach
        </div>
        @if($TopicBlock->more_btn_status)
            @slot('actions')
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endslot
        @endif
    @endcomponent
@endif

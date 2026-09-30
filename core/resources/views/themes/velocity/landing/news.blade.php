@php
    $velocityNews = ($TopicBlockContents->module_id ?? 0) > 0
        ? Helper::Topics(
            $TopicBlockContents->module_id,
            $TopicBlockContents->category_ids ?? '',
            $TopicBlockContents->records_count ?? 7,
            0,
            $TopicBlockContents->records_order ?? 0
        )
        : collect();
    $velocityLead = $velocityNews->first();
    $velocitySectionUrl = ($TopicBlockContents->module_id ?? 0) > 0 ? Helper::sectionURL($TopicBlockContents->module_id) : null;
@endphp
@if($velocityNews->isNotEmpty())
    @component(theme_view('components.landing-section'), ['block' => $TopicBlock, 'contents' => $TopicBlockContents])
        <div class="velocity-news-layout">
            <div class="velocity-news-layout__lead">
                @include(theme_view('components.topic-card'), [
                    'topic' => $velocityLead,
                    'variant' => 'news',
                    'showMeta' => true,
                    'featured' => true,
                    'loading' => 'eager',
                ])
            </div>
            <div class="velocity-news-layout__list" role="list">
                @foreach($velocityNews->slice(1) as $velocityNewsItem)
                    <div class="velocity-news-layout__item" role="listitem">
                        @include(theme_view('components.topic-card'), [
                            'topic' => $velocityNewsItem,
                            'variant' => 'news',
                            'showMeta' => true,
                        ])
                    </div>
                @endforeach
            </div>
        </div>
        @if($TopicBlock->more_btn_status)
            @slot('actions')
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            @endslot
        @endif
    @endcomponent
@endif

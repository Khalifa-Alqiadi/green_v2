@extends(theme_view('layouts.master'))

@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityTitle = $Topic->{'title_'.$velocityLanguageCode} ?: $Topic->{'title_'.$velocityDefaultCode};
    $velocityDetailsField = filled($Topic->{'details_'.$velocityLanguageCode})
        ? 'details_'.$velocityLanguageCode
        : 'details_'.$velocityDefaultCode;
    $velocitySectionTitle = $WebmasterSection->{'title_'.$velocityLanguageCode}
        ?: $WebmasterSection->{'title_'.$velocityDefaultCode};
    $velocityCategory = is_object($CurrentCategory ?? null) ? $CurrentCategory : null;
    $velocityCategoryTitle = $velocityCategory
        ? ($velocityCategory->{'title_'.$velocityLanguageCode} ?: $velocityCategory->{'title_'.$velocityDefaultCode})
        : null;
    $velocityCover = $velocityCategory?->photo
        ? route('fileView', ['path' => 'sections/'.$velocityCategory->photo])
        : ($WebmasterSection->photo ? route('fileView', ['path' => 'topics/'.$WebmasterSection->photo]) : null);
    $velocityHasSidebar = collect($Categories ?? [])->count() > 1;
    $velocityEditorial = (bool) $WebmasterSection->date_status;
    $velocityTopicCategories = collect($Topic->categories ?? [])->pluck('section')->filter();
    $velocityTopicAuthors = $Topic->authors->where('status', true);
    $velocityCustomCss = (string) $WebmasterSection->css_code
        .(string) ($velocityCategory?->css_code ?? '')
        .(string) $Topic->css_code;
    $velocityCustomJs = (string) $WebmasterSection->js_code
        .(string) ($velocityCategory?->js_code ?? '')
        .(string) $Topic->js_code;
    $velocityCustomBody = (string) $WebmasterSection->body_code
        .(string) ($velocityCategory?->body_code ?? '')
        .(string) $Topic->body_code;
    $velocityPrimaryImage = $Topic->photo_file;
    if(blank($velocityPrimaryImage)) {
        $velocityImageFieldIds = collect($WebmasterSection->customFields)
            ->where('type', 8)
            ->filter(fn ($field) => in_array($field->lang_code, ['all', $velocityLanguageCode], true))
            ->pluck('id');
        $velocityPrimaryImage = optional(collect($Topic->fields)->first(
            fn ($field) => $velocityImageFieldIds->contains($field->field_id) && filled($field->field_value)
        ))->field_value;
    }
    $velocityGalleryImages = collect();
    if(filled($velocityPrimaryImage)) {
        $velocityGalleryImages->push([
            'file' => $velocityPrimaryImage,
            'title' => Helper::topicPhotoAlt($Topic),
        ]);
    }
    foreach(collect($Topic->photos) as $velocityPhoto) {
        $velocityGalleryImages->push([
            'file' => $velocityPhoto->file,
            'title' => $velocityPhoto->title ?: $velocityTitle,
        ]);
    }
    $velocityGalleryId = 'velocity-media-gallery-'.$Topic->id;
@endphp

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $velocityTitle,
        'breadcrumbs' => array_values(array_filter([
            [
                'label' => $velocitySectionTitle,
                'url' => Helper::sectionURL($WebmasterSection->id, '', $WebmasterSection),
            ],
            $velocityCategory ? [
                'label' => $velocityCategoryTitle,
                'url' => Helper::categoryURL($velocityCategory->id, '', $velocityCategory),
            ] : null,
        ])),
        'eyebrow' => $velocityCategoryTitle ?: $velocitySectionTitle,
        'variant' => $velocityCover ? 'media' : ($velocityEditorial ? 'editorial' : 'gradient'),
        'media' => $velocityCover,
    ])

    @if(!$Topic->status)
        <div class="velocity-container">
            @component(theme_view('components.status'), ['tone' => 'warning'])
                {{ __('backend.pageIsNotPublished') }}
            @endcomponent
        </div>
    @endif

    <section class="velocity-section" id="content">
        <div class="velocity-container">
            <div class="velocity-content-layout {{ $velocityEditorial ? 'velocity-content-layout--editorial' : 'velocity-content-layout--service' }} {{ $velocityHasSidebar ? '' : 'velocity-content-layout--full' }}">
                @if($velocityHasSidebar)
                    @include(theme_view('layouts.side'))
                @endif

                <article class="velocity-topic-detail {{ $velocityEditorial ? 'velocity-topic-detail--editorial' : 'velocity-topic-detail--service' }}">
                    @if((int) $WebmasterSection->type === 2 && filled($Topic->video_file))
                        <div class="velocity-media-frame velocity-media-frame--video">
                            @if((int) $Topic->video_type === 1)
                                @php($velocityVideoId = Helper::Get_youtube_video_id($Topic->video_file))
                                @if($velocityVideoId)
                                    <iframe
                                        src="https://www.youtube-nocookie.com/embed/{{ urlencode($velocityVideoId) }}?autoplay=1&amp;mute=1"
                                        title="{{ $velocityTitle }}"
                                        loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen
                                    ></iframe>
                                @endif
                            @elseif((int) $Topic->video_type === 2)
                                @php($velocityVideoId = Helper::Get_vimeo_video_id($Topic->video_file))
                                @if($velocityVideoId)
                                    <iframe
                                        src="https://player.vimeo.com/video/{{ urlencode($velocityVideoId) }}?title=0&amp;byline=0&amp;autoplay=1&amp;muted=1"
                                        title="{{ $velocityTitle }}"
                                        loading="lazy"
                                        allow="autoplay; fullscreen; picture-in-picture"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen
                                    ></iframe>
                                @endif
                            @elseif((int) $Topic->video_type === 3)
                                <div class="velocity-embed">{!! $Topic->video_file !!}</div>
                            @else
                                <video controls autoplay muted playsinline preload="metadata" data-velocity-video-player @if($velocityPrimaryImage) poster="{{ route('fileView', ['path' => 'topics/'.$velocityPrimaryImage]) }}" @endif>
                                    <source src="{{ route('fileView', ['path' => 'topics/'.$Topic->video_file]) }}" type="video/{{ strtolower(pathinfo($Topic->video_file, PATHINFO_EXTENSION)) }}">
                                    <a href="{{ route('fileView', ['path' => 'topics/'.$Topic->video_file]) }}">{{ __('frontend.downloadAttach') }}</a>
                                </video>
                            @endif
                        </div>
                    @elseif((int) $WebmasterSection->type === 3 && filled($Topic->audio_file))
                        <div class="velocity-audio-card velocity-panel">
                            @if($velocityPrimaryImage)
                                <img
                                    src="{{ route('fileView', ['path' => 'topics/'.$velocityPrimaryImage]) }}?w=1200&h=675&r=fit"
                                    srcset="{{ route('fileView', ['path' => 'topics/'.$velocityPrimaryImage]) }}?w=640&h=360&r=fit 640w, {{ route('fileView', ['path' => 'topics/'.$velocityPrimaryImage]) }}?w=1200&h=675&r=fit 1200w"
                                    sizes="(max-width: 55rem) 100vw, 70vw"
                                    width="1200"
                                    height="675"
                                    alt="{{ Helper::topicPhotoAlt($Topic) }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @endif
                            @if((int) $Topic->video_type === 3)
                                <div class="velocity-embed">{!! $Topic->audio_file !!}</div>
                            @else
                                <div class="velocity-audio-player" data-velocity-audio-player data-play-label="{{ __('frontend.play') }}" data-pause-label="{{ __('frontend.pause') }}">
                                    <audio controls preload="metadata">
                                        <source src="{{ route('fileView', ['path' => 'topics/'.$Topic->audio_file]) }}" type="audio/mpeg">
                                        <a href="{{ route('fileView', ['path' => 'topics/'.$Topic->audio_file]) }}">{{ __('frontend.downloadAttach') }}</a>
                                    </audio>
                                </div>
                            @endif
                        </div>
                    @elseif($velocityGalleryImages->isNotEmpty())
                        <div id="{{ $velocityGalleryId }}" class="velocity-detail-gallery" data-velocity-gallery>
                            @foreach($velocityGalleryImages as $velocityImage)
                                <a
                                    href="{{ route('fileView', ['path' => 'topics/'.$velocityImage['file']]) }}"
                                    data-velocity-lightbox
                                    data-gallery="{{ $velocityGalleryId }}"
                                    data-title="{{ $velocityImage['title'] }}"
                                    @if(!$loop->first) class="velocity-detail-gallery__thumbnail" @endif
                                >
                                    <img
                                        src="{{ route('fileView', ['path' => 'topics/'.$velocityImage['file']]) }}?w={{ $loop->first ? 1400 : 480 }}&h={{ $loop->first ? 788 : 320 }}&r=fit"
                                        @if($loop->first)
                                            srcset="{{ route('fileView', ['path' => 'topics/'.$velocityImage['file']]) }}?w=640&h=360&r=fit 640w, {{ route('fileView', ['path' => 'topics/'.$velocityImage['file']]) }}?w=1000&h=563&r=fit 1000w, {{ route('fileView', ['path' => 'topics/'.$velocityImage['file']]) }}?w=1400&h=788&r=fit 1400w"
                                            sizes="(max-width: 55rem) 100vw, 70vw"
                                        @endif
                                        width="{{ $loop->first ? 1400 : 480 }}"
                                        height="{{ $loop->first ? 788 : 320 }}"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                        decoding="async"
                                        @if($loop->first) fetchpriority="high" @endif
                                        alt="{{ $velocityImage['title'] }}"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($velocityTopicCategories->isNotEmpty() || $velocityTopicAuthors->isNotEmpty() || $WebmasterSection->date_status || $WebmasterSection->visits_status)
                        <div class="velocity-topic-meta" role="list">
                            @if(Helper::GeneralWebmasterSettings('authors_status') && $velocityTopicAuthors->isNotEmpty())
                                <div class="velocity-topic-meta__item velocity-topic-meta__item--authors" role="listitem">
                                    @include(theme_view('components.author-links'), ['authors' => $velocityTopicAuthors, 'class' => 'velocity-topic-meta__authors'])
                                </div>
                            @endif
                            @if($velocityTopicCategories->isNotEmpty())
                                <div class="velocity-topic-meta__item velocity-topic-meta__item--categories" role="listitem">
                                    <span class="velocity-topic-meta__icon">
                                        @include(theme_view('components.icon'), ['name' => 'folder'])
                                    </span>
                                    <span class="velocity-topic-meta__categories">
                                        @foreach($velocityTopicCategories as $velocityTopicCategory)
                                            <a href="{{ Helper::categoryURL($velocityTopicCategory->id, '', $velocityTopicCategory) }}">
                                                {{ $velocityTopicCategory->{'title_'.$velocityLanguageCode} ?: $velocityTopicCategory->{'title_'.$velocityDefaultCode} }}
                                            </a>
                                        @endforeach
                                    </span>
                                </div>
                            @endif
                            @if($WebmasterSection->date_status)
                                <div class="velocity-topic-meta__item" role="listitem">
                                    <span class="velocity-topic-meta__icon">
                                        @include(theme_view('components.icon'), ['name' => 'calendar'])
                                    </span>
                                    <time datetime="{{ $Topic->date }}">{{ Helper::formatDate($Topic->date) }}</time>
                                </div>
                            @endif
                            @if($WebmasterSection->visits_status)
                                <div class="velocity-topic-meta__item" role="listitem">
                                    <span class="velocity-topic-meta__icon">
                                        @include(theme_view('components.icon'), ['name' => 'eye'])
                                    </span>
                                    <span>{{ number_format($Topic->visits) }} {{ __('backend.visits') }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @include(theme_view('topic.fields'), [
                        'Fields' => collect($WebmasterSection->customFields)->where('in_page', true)->where('type', '!=', 9),
                    ])

                    @if(filled($Topic->{$velocityDetailsField}))
                        <div class="velocity-rich-content velocity-topic-body" data-velocity-reveal>
                            @if($WebmasterSection->editor_status)
                                {!! str_replace('"#', '"'.Request::url().'#', $Topic->{$velocityDetailsField}) !!}
                            @else
                                {!! nl2br(e($Topic->{$velocityDetailsField})) !!}
                            @endif
                        </div>
                    @endif

                    @if(filled($Topic->attach_file))
                        <a class="velocity-primary-file velocity-panel" href="{{ route('fileView', ['path' => 'topics/'.$Topic->attach_file]) }}" target="_blank" rel="noopener">
                            <span aria-hidden="true">↓</span>
                            <strong>{{ __('frontend.downloadAttach') }}</strong>
                            <small>{{ basename($Topic->attach_file) }}</small>
                        </a>
                    @endif

                    @include(theme_view('topic.fields'), [
                        'Fields' => collect($WebmasterSection->customFields)->where('in_page', true)->where('type', 9),
                    ])
                    @include(theme_view('topic.files'))
                    @include(theme_view('topic.maps'))
                    @include(theme_view('topic.tags'))

                    @if((int) $WebmasterSection->type === 7)
                        <a class="velocity-button velocity-button--secondary velocity-button--wide" href="{{ Helper::sectionURL($Topic->webmaster_id) }}">
                            {{ __('backend.clickToNewSearch') }}
                        </a>
                    @else
                        @include(theme_view('topic.share'))
                    @endif

                    @include(theme_view('topic.comments'))

                    @if((int) $Topic->form_id > 0)
                        <section class="velocity-topic-section">
                            @include(theme_view('form'), ['FormSectionID' => $Topic->form_id])
                        </section>
                    @elseif($WebmasterSection->order_status)
                        @include(theme_view('topic.order'))
                    @endif

                    @include(theme_view('topic.next_prev'))
                    @include(theme_view('topic.related'))
                </article>
            </div>
        </div>
    </section>

    @include(theme_view('layouts.popup'), ['Popup' => $Popup ?? null])
@endsection

@if($velocityCustomCss !== '')
    @push('after-styles')
        <style data-velocity-topic-css>{!! $velocityCustomCss !!}</style>
    @endpush
@endif
@if($velocityCustomJs !== '')
    @push('after-scripts')
        {!! $velocityCustomJs !!}
    @endpush
@endif
@if($velocityCustomBody !== '')
    @push('before-footer')
        {!! Blade::render($velocityCustomBody) !!}
    @endpush
@endif

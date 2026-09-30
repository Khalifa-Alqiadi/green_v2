@extends(theme_view('layouts.master'))

@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityCategory = is_object($CurrentCategory ?? null) ? $CurrentCategory : null;
    $velocitySectionTitle = $WebmasterSection->{'title_'.$velocityLanguageCode}
        ?: $WebmasterSection->{'title_'.$velocityDefaultCode};
    $velocityCategoryTitle = $velocityCategory
        ? ($velocityCategory->{'title_'.$velocityLanguageCode} ?: $velocityCategory->{'title_'.$velocityDefaultCode})
        : null;
    $velocityPageTitle = $velocityCategoryTitle ?: $velocitySectionTitle;
    $velocityAllTopics = collect(Helper::Topics($WebmasterSection->id, 0, 0));
    $velocityCustomCss = (string) $WebmasterSection->css_code.(string) ($velocityCategory?->css_code ?? '');
    $velocityCustomJs = (string) $WebmasterSection->js_code.(string) ($velocityCategory?->js_code ?? '');
    $velocityCustomBody = (string) $WebmasterSection->body_code.(string) ($velocityCategory?->body_code ?? '');
    $velocityHeadingMedia = $WebmasterSection->photo
        ? route('fileView', ['path' => 'topics/'.$WebmasterSection->photo])
        : null;
@endphp

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $velocityPageTitle,
        'breadcrumbs' => [
            [
                'label' => $velocitySectionTitle,
                'url' => Helper::sectionURL($WebmasterSection->id, '', $WebmasterSection),
            ],
            ...($velocityCategoryTitle ? [['label' => $velocityCategoryTitle]] : []),
        ],
        'eyebrow' => $velocitySectionTitle,
        'variant' => $velocityHeadingMedia ? 'media' : 'gradient',
        'media' => $velocityHeadingMedia,
    ])

    <section class="velocity-section" id="content">
        <div class="velocity-container">
            @if(collect($Categories)->isNotEmpty())
                <div class="velocity-faq-sections" data-velocity-reveal>
                    @foreach($Categories as $Category)
                        @php
                            $velocityCategoryName = $Category->{'title_'.$velocityLanguageCode}
                                ?: $Category->{'title_'.$velocityDefaultCode};
                            $velocityCategoryTopics = $velocityAllTopics->filter(
                                fn ($topic) => collect($topic->categories)->contains('section_id', $Category->id)
                            );
                        @endphp
                        <section aria-labelledby="faq-category-{{ $Category->id }}">
                            <h2 id="faq-category-{{ $Category->id }}" class="velocity-subsection-title">
                                @if($Category->icon)
                                    <i class="{{ $Category->icon }}" aria-hidden="true"></i>
                                @endif
                                {{ $velocityCategoryName }}
                            </h2>
                            @if($velocityCategoryTopics->isNotEmpty())
                                <div class="velocity-faq-grid">
                                    @foreach($velocityCategoryTopics as $Topic)
                                        @include(theme_view('topic.accordion'), ['Topic' => $Topic, 'CatId' => $Category->id])
                                    @endforeach
                                </div>
                            @else
                                @include(theme_view('components.empty-state'), ['class' => 'velocity-empty--compact'])
                            @endif
                        </section>
                    @endforeach
                </div>
            @elseif($velocityAllTopics->isNotEmpty())
                <div class="velocity-faq-grid">
                    @foreach($velocityAllTopics as $Topic)
                        @include(theme_view('topic.accordion'), ['Topic' => $Topic, 'CatId' => 0])
                    @endforeach
                </div>
            @else
                @include(theme_view('components.empty-state'))
            @endif
        </div>
    </section>

    @include(theme_view('layouts.popup'), ['Popup' => $Popup ?? null])
@endsection

@if($velocityCustomCss !== '')
    @push('after-styles')
        <style data-velocity-module-css>{!! $velocityCustomCss !!}</style>
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

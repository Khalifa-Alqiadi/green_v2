@extends(theme_view('layouts.master'))

@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocitySectionTitle = $WebmasterSection->{'title_'.$velocityLanguageCode}
        ?: $WebmasterSection->{'title_'.$velocityDefaultCode};
    $velocityAllTopics = collect(Helper::Topics($WebmasterSection->id, 0, 0));
    $velocityCustomCss = (string) $WebmasterSection->css_code;
    $velocityCustomJs = (string) $WebmasterSection->js_code;
    $velocityCustomBody = (string) $WebmasterSection->body_code;
@endphp

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $velocitySectionTitle,
        'breadcrumbs' => [['label' => $velocitySectionTitle]],
    ])

    <section class="velocity-section" id="content">
        <div class="velocity-container velocity-docs-layout">
            <nav class="velocity-docs-nav velocity-panel" aria-label="{{ $velocitySectionTitle }}">
                @if(collect($Categories)->isNotEmpty())
                    @foreach($Categories as $Category)
                        @php
                            $velocityCategoryTitle = $Category->{'title_'.$velocityLanguageCode}
                                ?: $Category->{'title_'.$velocityDefaultCode};
                            $velocityCategoryAnchor = 'docs-category-'.$Category->id;
                            $velocityCategoryTopics = $velocityAllTopics->filter(
                                fn ($topic) => collect($topic->categories)->contains('section_id', $Category->id)
                            );
                        @endphp
                        <a class="velocity-docs-nav__category" href="#{{ $velocityCategoryAnchor }}">{{ $velocityCategoryTitle }}</a>
                        @foreach($velocityCategoryTopics as $velocityDocTopic)
                            @php
                                $velocityDocTitle = $velocityDocTopic->{'title_'.$velocityLanguageCode}
                                    ?: $velocityDocTopic->{'title_'.$velocityDefaultCode};
                            @endphp
                            <a href="#docs-topic-{{ $velocityDocTopic->id }}">{{ $velocityDocTitle }}</a>
                        @endforeach
                    @endforeach
                @else
                    @foreach($velocityAllTopics as $velocityDocTopic)
                        @php
                            $velocityDocTitle = $velocityDocTopic->{'title_'.$velocityLanguageCode}
                                ?: $velocityDocTopic->{'title_'.$velocityDefaultCode};
                        @endphp
                        <a href="#docs-topic-{{ $velocityDocTopic->id }}">{{ $velocityDocTitle }}</a>
                    @endforeach
                @endif
            </nav>

            <div class="velocity-docs-content">
                @if(collect($Categories)->isNotEmpty())
                    @foreach($Categories as $Category)
                        @php
                            $velocityCategoryTitle = $Category->{'title_'.$velocityLanguageCode}
                                ?: $Category->{'title_'.$velocityDefaultCode};
                            $velocityCategoryTopics = $velocityAllTopics->filter(
                                fn ($topic) => collect($topic->categories)->contains('section_id', $Category->id)
                            );
                        @endphp
                        <section aria-labelledby="docs-category-{{ $Category->id }}">
                            <h2 id="docs-category-{{ $Category->id }}" class="velocity-subsection-title">{{ $velocityCategoryTitle }}</h2>
                            @forelse($velocityCategoryTopics as $Topic)
                                @include(theme_view('components.doc-entry'), ['Topic' => $Topic])
                            @empty
                                @include(theme_view('components.empty-state'), ['class' => 'velocity-empty--compact'])
                            @endforelse
                        </section>
                    @endforeach
                @else
                    @forelse($velocityAllTopics as $Topic)
                        @include(theme_view('components.doc-entry'), ['Topic' => $Topic])
                    @empty
                        @include(theme_view('components.empty-state'))
                    @endforelse
                @endif
            </div>
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

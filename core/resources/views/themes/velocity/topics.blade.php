@extends(theme_view('layouts.master'))

@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocitySection = is_object($WebmasterSection ?? null) ? $WebmasterSection : null;
    $velocityCategory = is_object($CurrentCategory ?? null) ? $CurrentCategory : null;
    $velocitySectionTitle = $velocitySection
        ? ($velocitySection->{'title_'.$velocityLanguageCode} ?: $velocitySection->{'title_'.$velocityDefaultCode})
        : null;
    $velocityCategoryTitle = $velocityCategory
        ? ($velocityCategory->{'title_'.$velocityLanguageCode} ?: $velocityCategory->{'title_'.$velocityDefaultCode})
        : null;
    $velocityPageTitle = $DBTag->title ?? $velocityCategoryTitle ?? $velocitySectionTitle ?? $PageTitle;
    $velocityDescription = $velocityCategory
        ? ($velocityCategory->{'details_'.$velocityLanguageCode} ?: $velocityCategory->{'details_'.$velocityDefaultCode})
        : null;
    $velocityDescription = ($page_type ?? null) === 'tag' && filled($TagDescription ?? null)
        ? $TagDescription
        : $velocityDescription;
    $velocityCategoryImage = $velocityCategory?->photo
        ? route('fileView', ['path' => 'sections/'.$velocityCategory->photo])
        : ($velocitySection?->photo ? route('fileView', ['path' => 'topics/'.$velocitySection->photo]) : null);
    $velocityCustomCss = (string) ($velocitySection?->css_code ?? '').(string) ($velocityCategory?->css_code ?? '');
    $velocityCustomJs = (string) ($velocitySection?->js_code ?? '').(string) ($velocityCategory?->js_code ?? '');
    $velocityCustomBody = (string) ($velocitySection?->body_code ?? '').(string) ($velocityCategory?->body_code ?? '');
    $velocityHasSidebar = collect($Categories ?? [])->isNotEmpty();
    $velocityIsSectionIndex = (int) ($velocitySection?->type ?? -1) === 0;
    $velocityIsFirstPage = (int) $Topics->currentPage() === 1;
    $velocityLeadTopic = $velocityIsSectionIndex && $velocityIsFirstPage ? $Topics->first() : null;
    $velocitySupportingTopics = $velocityLeadTopic ? $Topics->slice(1) : $Topics;
    $velocitySectionIndexMode = $velocityHasSidebar ? 'categorized' : 'uncategorized';
    $velocityEditorial = (bool) ($velocitySection?->date_status ?? false);
    $velocityListingVariant = $velocityEditorial ? 'editorial' : 'service';
    $velocityHasFieldFilters = collect(request()->query())->keys()->contains(
        fn ($key) => str_starts_with((string) $key, 'customField_')
    );
    $velocityPaginationCount = (int) config('smartend.frontend_pagination');
    $velocityListEndpoint = $velocitySection
        ? route('HomeTopicsList', [
            'id' => $velocitySection->id,
            'cat' => $velocityCategory?->id ?? 0,
            'count' => $velocityPaginationCount,
        ]).'?'.http_build_query(array_filter([
            'tag' => $DBTag->id ?? null,
            'lang' => $velocityLanguageCode,
            'search_word' => $search_word ?? null,
        ], fn ($value) => $value !== null && $value !== ''))
        : null;
    $velocityBreadcrumbs = collect([
        $velocitySectionTitle ? [
            'label' => $velocitySectionTitle,
            'url' => $velocitySection ? Helper::sectionURL($velocitySection->id, '', $velocitySection) : null,
        ] : null,
        $velocityCategoryTitle ? ['label' => $velocityCategoryTitle] : null,
        !empty($DBTag) ? ['label' => $DBTag->title] : null,
        filled($search_word ?? null) ? ['label' => __('backend.search')] : null,
    ])->filter()->values()->all();
@endphp

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => filled($search_word ?? null)
            ? __('backend.resultsFoundFor').' [ '.$search_word.' ]'
            : $velocityPageTitle,
        'description' => $velocityDescription,
        'breadcrumbs' => $velocityBreadcrumbs,
        'eyebrow' => $velocityCategoryTitle ?: $velocitySectionTitle,
        'variant' => $velocityCategoryImage ? 'media' : 'gradient',
        'media' => $velocityCategoryImage,
    ])

    <section class="velocity-section" id="content">
        <div class="velocity-container">

            <div class="velocity-content-layout velocity-content-layout--{{ $velocityListingVariant }} {{ $velocityHasSidebar ? '' : 'velocity-content-layout--full' }}">
                @if($velocityHasSidebar && $velocitySection)
                    @include(theme_view('layouts.side'))
                @endif

                <div class="velocity-results" aria-live="polite">
                    @if($Topics->total() === 0)
                        @include(theme_view('components.empty-state'))
                    @else


                        @if($velocityIsSectionIndex)
                            <div class="velocity-section-listing velocity-section-listing--{{ $velocitySectionIndexMode }}">
                                @if($velocityLeadTopic)
                                    <div class="velocity-section-listing__lead">
                                        @include(theme_view('components.topic-card'), [
                                            'topic' => $velocityLeadTopic,
                                            'variant' => 'listing-featured',
                                            'showMeta' => true,
                                            'showFields' => true,
                                            'featured' => true,
                                            'loading' => 'eager',
                                        ])
                                    </div>
                                @endif

                                <div
                                    class="velocity-topic-grid velocity-topic-grid--section-index velocity-topic-grid--{{ $velocitySectionIndexMode }}"
                                    id="velocity-topic-results"
                                    data-velocity-topic-results
                                >
                                    @foreach($velocitySupportingTopics as $Topic)
                                        @include(theme_view('components.topic-card'), [
                                            'topic' => $Topic,
                                            'variant' => 'service',
                                            'showMeta' => true,
                                            'showFields' => true,
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="velocity-topic-grid velocity-topic-grid--{{ $velocityListingVariant }}" id="velocity-topic-results" data-velocity-topic-results>
                                @foreach($Topics as $Topic)
                                    @include(theme_view('topic.card'), ['Topic' => $Topic])
                                @endforeach
                            </div>
                        @endif

                        @php
                            $Topics->appends(request()->except('page'));
                        @endphp
                        <div id="velocity-topic-pagination" data-velocity-native-pagination>
                            {!! $Topics->onEachSide(1)->links(theme_view('components.pagination')) !!}
                        </div>

                        @if($Topics->hasMorePages() && $velocityListEndpoint && !$velocityHasFieldFilters)
                            @php
                                $velocityExplorerConfig = [
                                    'target' => 'velocity-topic-results',
                                    'paginationTarget' => 'velocity-topic-pagination',
                                    'endpoint' => $velocityListEndpoint,
                                    'page' => $Topics->currentPage(),
                                    'lastPage' => $Topics->lastPage(),
                                    'loadLabel' => __('frontend.viewMore'),
                                    'loadingLabel' => __('backend.loading'),
                                    'errorLabel' => __('backend.error'),
                                    'resultsLabel' => __('backend.resultsFoundFor'),
                                ];
                            @endphp
                            <div
                                class="velocity-topic-explorer"
                                data-velocity-component="topic-explorer"
                                data-velocity-config="velocity-topic-explorer-config"
                            ></div>
                            <script id="velocity-topic-explorer-config" type="application/json" data-velocity-config>
                                @json($velocityExplorerConfig)
                            </script>
                        @endif
                    @endif
                </div>
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

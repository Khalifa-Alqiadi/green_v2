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
    $velocityPermissionId = Auth::user()?->permissions_id;
    $velocityFieldIsVisible = function ($field) use ($velocityLanguageCode, $velocityPermissionId) {
        if (!in_array($field->lang_code, ['all', $velocityLanguageCode], true)) {
            return false;
        }

        $groups = array_filter(array_map('trim', explode(',', (string) $field->view_permission_groups)));

        return $groups === []
            || in_array('0', $groups, true)
            || ($velocityPermissionId !== null && in_array((string) $velocityPermissionId, $groups, true));
    };
    $velocityVisibleFields = collect($WebmasterSection->customFields)
        ->filter(fn ($field) => $field->in_listing
            && (int) $field->type !== 99
            && $velocityFieldIsVisible($field));
    $velocityStatisticFields = collect($WebmasterSection->customFields)
        ->filter(fn ($field) => $field->in_statics
            && in_array((int) $field->type, [6, 7, 13, 17], true)
            && $velocityFieldIsVisible($field));
    $velocityCustomCss = (string) $WebmasterSection->css_code.(string) ($velocityCategory?->css_code ?? '');
    $velocityCustomJs = (string) $WebmasterSection->js_code.(string) ($velocityCategory?->js_code ?? '');
    $velocityCustomBody = (string) $WebmasterSection->body_code.(string) ($velocityCategory?->body_code ?? '');
    $velocityHasSidebar = collect($Categories ?? [])->isNotEmpty();
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
    ])

    <section class="velocity-section" id="content">
        <div class="velocity-container">
            <div class="velocity-filter-panel velocity-panel">
                @include(theme_view('layouts.search'))
            </div>

            <div class="velocity-content-layout {{ $velocityHasSidebar ? '' : 'velocity-content-layout--full' }}">
                @if($velocityHasSidebar)
                    @include(theme_view('layouts.side'))
                @endif

                <div class="velocity-results">
                    @if($Topics->total() === 0)
                        @include(theme_view('components.empty-state'))
                    @else
                        <p class="velocity-results__count">{{ $Topics->total() }} {{ __('backend.resultsFoundFor') }}</p>
                        <div class="velocity-table-wrap velocity-panel" tabindex="0" role="region" aria-label="{{ $velocityPageTitle }}">
                            <table class="velocity-data-table">
                                <thead>
                                    <tr>
                                        @if($WebmasterSection->no_status)
                                            <th scope="col">ID</th>
                                        @endif
                                        @if($WebmasterSection->title_status)
                                            <th scope="col">{{ __('backend.topicName') }}</th>
                                        @endif
                                        @foreach($velocityVisibleFields as $velocityField)
                                            <th scope="col">
                                                {{ $velocityField->{'title_'.$velocityLanguageCode} ?: $velocityField->{'title_'.$velocityDefaultCode} }}
                                            </th>
                                        @endforeach
                                        @if($WebmasterSection->date_status)
                                            <th scope="col">{{ __('backend.topicDate') }}</th>
                                        @endif
                                        @if($WebmasterSection->expire_date_status)
                                            <th scope="col">{{ __('backend.expireDate') }}</th>
                                        @endif
                                        @if($WebmasterSection->longtext_status)
                                            <th scope="col">{{ __('backend.viewDetails') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($Topics as $Topic)
                                        @php
                                            $velocityTopicTitle = $Topic->{'title_'.$velocityLanguageCode}
                                                ?: $Topic->{'title_'.$velocityDefaultCode};
                                            $velocityTopicUrl = Helper::topicURL($Topic->id, '', $Topic);
                                        @endphp
                                        <tr>
                                            @if($WebmasterSection->no_status)
                                                <td>{{ $Topic->id }}</td>
                                            @endif
                                            @if($WebmasterSection->title_status)
                                                <th scope="row">
                                                    <a href="{{ $velocityTopicUrl }}">{{ $velocityTopicTitle }}</a>
                                                </th>
                                            @endif
                                            @foreach($velocityVisibleFields as $velocityField)
                                                @php
                                                    $velocityFieldLabel = $velocityField->{'title_'.$velocityLanguageCode}
                                                        ?: $velocityField->{'title_'.$velocityDefaultCode};
                                                    $velocityFieldValue = optional(
                                                        collect($Topic->fields)->firstWhere('field_id', $velocityField->id)
                                                    )->field_value;
                                                @endphp
                                                <td data-label="{{ $velocityFieldLabel }}">
                                                    @include(theme_view('components.topic-field-value'), [
                                                        'field' => $velocityField,
                                                        'value' => $velocityFieldValue,
                                                        'label' => $velocityFieldLabel,
                                                    ])
                                                </td>
                                            @endforeach
                                            @if($WebmasterSection->date_status)
                                                <td><time datetime="{{ $Topic->date }}">{{ Helper::formatDate($Topic->date) }}</time></td>
                                            @endif
                                            @if($WebmasterSection->expire_date_status)
                                                <td>
                                                    @if($Topic->expire_date)
                                                        <time datetime="{{ $Topic->expire_date }}">{{ Helper::formatDate($Topic->expire_date) }}</time>
                                                    @else
                                                        <span>—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            @if($WebmasterSection->longtext_status)
                                                <td><a href="{{ $velocityTopicUrl }}">{{ __('backend.viewDetails') }}</a></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @php
                            $Topics->appends(request()->except('page'));
                        @endphp
                        {!! $Topics->onEachSide(1)->links(theme_view('components.pagination')) !!}
                    @endif
                </div>
            </div>

            @if(collect($statics ?? [])->isNotEmpty())
                <section class="velocity-statistics" aria-labelledby="velocity-statistics-title">
                    <h2 id="velocity-statistics-title">{{ __('backend.reports') }}</h2>
                    <div class="velocity-statistics__grid">
                        @foreach($velocityStatisticFields as $velocityField)
                            @continue(empty($statics[$velocityField->id] ?? []))
                            @php
                                $velocityFieldTitle = $velocityField->{'title_'.$velocityLanguageCode}
                                    ?: $velocityField->{'title_'.$velocityDefaultCode};
                                $velocityOptions = preg_split(
                                    '/\r\n|[\r\n]/',
                                    (string) ($velocityField->{'details_'.$velocityLanguageCode}
                                        ?: $velocityField->{'details_'.$velocityDefaultCode})
                                );
                                $velocityCounts = array_values($statics[$velocityField->id]);
                                $velocityColors = preg_split('/\r\n|[\r\n]/', (string) $velocityField->colors);
                                $velocityChartId = 'velocity-chart-'.$velocityField->id;
                                $velocityChartConfig = [
                                    'labels' => array_values($velocityOptions),
                                    'values' => $velocityCounts,
                                    'colors' => array_values($velocityColors),
                                ];
                            @endphp
                            <article class="velocity-stat-card velocity-panel">
                                <h3>{{ $velocityFieldTitle }}</h3>
                                <div
                                    id="{{ $velocityChartId }}"
                                    class="velocity-chart"
                                    data-velocity-chart
                                    data-velocity-config="{{ $velocityChartId }}-config"
                                    aria-hidden="true"
                                ></div>
                                <script id="{{ $velocityChartId }}-config" type="application/json">
                                    @json($velocityChartConfig)
                                </script>
                                <table class="velocity-chart-table">
                                    <caption class="velocity-visually-hidden">{{ $velocityFieldTitle }}</caption>
                                    <tbody>
                                        @foreach($velocityOptions as $velocityOptionIndex => $velocityOption)
                                            <tr>
                                                <th scope="row">{{ $velocityOption }}</th>
                                                <td>{{ $velocityCounts[$velocityOptionIndex] ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </article>
                        @endforeach
                    </div>
                </section>
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

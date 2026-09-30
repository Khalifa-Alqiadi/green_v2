@extends(theme_view('layouts.master'))

@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocitySectionTitle = $WebmasterSection->{'title_'.$velocityLanguageCode}
        ?: $WebmasterSection->{'title_'.$velocityDefaultCode};
    $velocitySearchField = collect($WebmasterSection->customFields)->first(
        fn ($field) => in_array((int) $field->type, [0, 2], true)
    );
    $velocityFieldTitle = $velocitySearchField
        ? ($velocitySearchField->{'title_'.$velocityLanguageCode} ?: $velocitySearchField->{'title_'.$velocityDefaultCode})
        : null;
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
        <div class="velocity-container velocity-narrow">
            @if($velocitySearchField)
                <div class="velocity-lookup velocity-panel">
                    <h2>{{ __('backend.type') }} {{ $velocityFieldTitle }} {{ __('backend.toSearch') }}</h2>
                    <form method="GET" action="{{ url()->current() }}" class="velocity-search-form" role="search">
                        <label class="velocity-visually-hidden" for="velocity-private-search">{{ $velocityFieldTitle }}</label>
                        <input
                            class="velocity-field"
                            id="velocity-private-search"
                            name="q"
                            type="{{ (int) $velocitySearchField->type === 2 ? 'number' : 'search' }}"
                            value="{{ request('q') }}"
                            placeholder="{{ __('backend.searchFor') }}..."
                            autocomplete="off"
                            required
                        >
                        <button class="velocity-button velocity-button--primary" type="submit">
                            @include(theme_view('components.icon'), ['name' => 'search'])
                            {{ __('backend.search') }}
                        </button>
                    </form>

                    @if(filled(request('q')))
                        @component(theme_view('components.status'), ['tone' => 'error'])
                            {{ __('backend.noResults') }}
                        @endcomponent
                    @endif
                </div>
            @else
                @component(theme_view('components.status'), ['tone' => 'error'])
                    {{ __('backend.error') }}
                @endcomponent
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

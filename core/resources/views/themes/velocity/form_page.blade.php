@extends(theme_view('layouts.master'))

@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityTitle = $WebmasterSection->{'title_'.$velocityLanguageCode}
        ?: $WebmasterSection->{'title_'.$velocityDefaultCode};
@endphp

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $velocityTitle,
        'breadcrumbs' => [['label' => $velocityTitle]],
    ])

    <section class="velocity-section" id="content">
        <div class="velocity-container velocity-narrow">
            @include(theme_view('form'), ['FormSectionID' => $WebmasterSection->id])
        </div>
    </section>
@endsection

@extends(theme_view('layouts.master'))

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $PageTitle,
        'breadcrumbs' => [],
    ])

    <section class="velocity-section" id="content">
        <div class="velocity-container">
            <article class="velocity-panel velocity-rich-content">
                <h1>{{ $PageTitle }}</h1>
                <p>{{ __('frontend.noData') }}</p>
            </article>
        </div>
    </section>
@endsection

@push('before-styles')
    {{-- Integrate custom CSS files here. --}}
@endpush

@push('after-styles')
    {{-- Integrate custom inline CSS here. --}}
@endpush

@push('after-scripts')
    {{-- Integrate custom JavaScript files here. --}}
@endpush

@section('footInclude')
    {{-- Integrate custom inline JavaScript here. --}}
@endsection

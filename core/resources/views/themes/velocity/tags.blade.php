@extends(theme_view('layouts.master'))

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $PageTitle,
        'breadcrumbs' => [['label' => $PageTitle]],
    ])

    <section class="velocity-section" id="content">
        <div class="velocity-container">
            @if($Tags->total() > 0)
                <ul class="velocity-tag-cloud" aria-label="{{ __('backend.tags') }}">
                    @foreach($Tags as $Tag)
                        <li>
                            <a href="{{ Helper::tagURL($Tag->id) }}">
                                <span>#{{ $Tag->title }}</span>
                                <span class="velocity-badge" aria-label="{{ $Tag->topics_count }}">{{ $Tag->topics_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @php
                    $Tags->appends(request()->except('page'));
                @endphp
                {!! $Tags->onEachSide(1)->links(theme_view('components.pagination')) !!}
            @else
                @include(theme_view('components.empty-state'))
            @endif
        </div>
    </section>
@endsection

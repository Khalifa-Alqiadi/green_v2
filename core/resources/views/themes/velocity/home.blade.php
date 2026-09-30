@extends(theme_view('layouts.master'))

@section('content')
    @php
        $title_var = 'title_'.@Helper::currentLanguage()->code;
        $title_var2 = 'title_'.config('smartend.default_language');
        $details_var = 'details_'.@Helper::currentLanguage()->code;
        $details_var2 = 'details_'.config('smartend.default_language');
    @endphp
    <div class="velocity-home" data-velocity-home>
        @include(theme_view('layouts.slider'), [
            'BannersSettingsId' => Helper::GeneralWebmasterSettings('home_banners_section_id'),
        ])
        @include(theme_view('homepage.row1'), ['Topic' => $Topic ?? null])
        @include(theme_view('homepage.row2'))
        @include(theme_view('homepage.row3'))
        @include(theme_view('homepage.row4'))
        @include(theme_view('homepage.row5'))
        @include(theme_view('homepage.row6'))
        @include(theme_view('homepage.row7'))
        @include(theme_view('layouts.popup'), ['Popup' => $Popup ?? null])
    </div>
@endsection

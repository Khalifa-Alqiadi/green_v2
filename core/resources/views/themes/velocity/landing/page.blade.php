@extends(theme_view('layouts.master'))

@section('content')
    @php
        $velocityCustomCss = (string) ($WebmasterSection->css_code ?? '').(string) ($Topic->css_code ?? '');
        $velocityCustomJs = (string) ($WebmasterSection->js_code ?? '').(string) ($Topic->js_code ?? '');
        $velocityCustomBody = (string) ($WebmasterSection->body_code ?? '').(string) ($Topic->body_code ?? '');
        $velocityBlocks = $Topic->topicBlocks->where('status', 1);
        $velocityFirstBlock = $velocityBlocks->first();
        $velocityFirstBlockContents = filled($velocityFirstBlock?->content)
            ? (json_decode($velocityFirstBlock->content) ?: (object) [])
            : (object) [];
        $velocityFirstBannerStyle = strtolower((string) preg_replace(
            '/[\s_-]+/',
            '',
            trim((string) ($velocityFirstBlockContents->banner_style ?? ''))
        ));
        $velocityLandingStartsWithSlider = (int) ($velocityFirstBlock?->type ?? -1) === 2
            && (int) ($velocityFirstBlockContents->banner_area_id ?? 0) > 0
            && $velocityFirstBannerStyle === 'slider';
        $velocityLanguageCode = Helper::currentLanguage()->code;
        $velocityDefaultCode = config('smartend.default_language');
        $velocityLandingTitle = $Topic->{'title_'.$velocityLanguageCode}
            ?: $Topic->{'title_'.$velocityDefaultCode}
            ?: ($PageTitle ?? '');
        $velocityLandingSectionTitle = is_object($WebmasterSection ?? null)
            ? ($WebmasterSection->{'title_'.$velocityLanguageCode}
                ?: $WebmasterSection->{'title_'.$velocityDefaultCode})
            : null;
        $velocityLandingCoverFile = $Topic->photo_file ?: ($WebmasterSection->photo ?? null);
        $velocityLandingCoverMedia = $velocityLandingCoverFile
            ? route('fileView', ['path' => 'topics/'.$velocityLandingCoverFile])
            : null;
        $velocityLandingBreadcrumbs = array_values(array_filter([
            filled($velocityLandingSectionTitle) && $velocityLandingSectionTitle !== $velocityLandingTitle
                ? [
                    'label' => $velocityLandingSectionTitle,
                    'url' => Helper::sectionURL($WebmasterSection->id, '', $WebmasterSection),
                ]
                : null,
            ['label' => $velocityLandingTitle],
        ]));
        $velocityContentViews = [
            'carousel' => 'landing.carousel',
            'categories' => 'landing.categories',
            'code' => 'landing.code',
            'contact' => 'landing.contact',
            'faq' => 'landing.faq',
            'form' => 'landing.form',
            'gallery' => 'landing.gallery',
            'grid' => 'landing.grid',
            'news' => 'landing.news',
            'partners' => 'landing.partners',
            'staff' => 'landing.staff',
            'static' => 'landing.static',
            'testimonials' => 'landing.testimonials',
        ];
        $velocityBannerViews = [
            'banners' => 'layouts.banners',
            'slider' => 'layouts.slider',
        ];
        $velocityResolveBlockView = static function ($style, array $views, string $kind): ?string {
            $normalized = strtolower((string) preg_replace('/[\s_-]+/', '', trim((string) $style)));

            if ($normalized === '') {
                return null;
            }

            if (isset($views[$normalized])) {
                return $views[$normalized];
            }

            \Illuminate\Support\Facades\Log::warning('Velocity ignored an unknown landing block style.', [
                'kind' => $kind,
                'style' => $style,
            ]);

            return null;
        };
    @endphp

    @unless($velocityLandingStartsWithSlider)
        @include(theme_view('components.page-heading'), [
            'title' => $velocityLandingTitle,
            'breadcrumbs' => $velocityLandingBreadcrumbs,
            'variant' => $velocityLandingCoverMedia ? 'media' : 'gradient',
            'media' => $velocityLandingCoverMedia,
        ])
    @endunless

    <div class="velocity-landing-page">
        @foreach($velocityBlocks as $TopicBlock)
            @if(filled($TopicBlock->content))
                @php
                    $TopicBlockContents = json_decode($TopicBlock->content) ?: (object) [];
                    $velocityBlockView = null;
                    $velocityBlockData = [];

                    if ((int) $TopicBlock->type === 4) {
                        $velocityBlockView = ($TopicBlockContents->module_id ?? 0) > 0
                            ? $velocityResolveBlockView($TopicBlockContents->view_style ?? 'form', $velocityContentViews, 'content')
                            : 'landing.contact';
                    } elseif ((int) $TopicBlock->type === 3) {
                        $velocityBlockView = $velocityResolveBlockView($TopicBlockContents->view_style ?? null, $velocityContentViews, 'content');
                    } elseif ((int) $TopicBlock->type === 2 && ($TopicBlockContents->banner_area_id ?? 0) > 0) {
                        $velocityBlockView = $velocityResolveBlockView($TopicBlockContents->banner_style ?? null, $velocityBannerViews, 'banner');
                        $velocityBlockData = ['BannersSettingsId' => $TopicBlockContents->banner_area_id];
                    } elseif ((int) $TopicBlock->type === 1) {
                        $velocityBlockView = 'landing.code';
                    } elseif ((int) $TopicBlock->type === 0) {
                        $velocityBlockView = 'landing.static';
                    }
                @endphp
                @if($velocityBlockView)
                    @include(theme_view($velocityBlockView), $velocityBlockData)
                @endif
            @endif
        @endforeach

        @include(theme_view('layouts.popup'), ['Popup' => $Popup ?? null])
    </div>
@endsection

@if(filled($velocityCustomCss) || filled($velocityCustomJs))
    @push('after-styles')
        @if(filled($velocityCustomCss))
            <style data-velocity-landing-css>{!! $velocityCustomCss !!}</style>
        @endif
        {!! $velocityCustomJs !!}
    @endpush
@endif
@if(filled($velocityCustomBody))
    @push('before-footer')
        {!! Blade::render($velocityCustomBody) !!}
    @endpush
@endif

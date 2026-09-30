@if(filled(config('smartend.google_maps_key')) && collect($Topic->maps)->isNotEmpty())
    @php
        $velocityLanguageCode = Helper::currentLanguage()->code;
        $velocityDefaultCode = config('smartend.default_language');
        $velocityMapId = 'velocity-map-'.$Topic->id;
        $velocityMapConfig = [
            'target' => $velocityMapId,
            'apiUrl' => 'https://maps.googleapis.com/maps/api/js?'.http_build_query([
                'key' => config('smartend.google_maps_key'),
                'language' => $velocityLanguageCode,
            ]),
            'locations' => collect($Topic->maps)->map(fn ($map) => [
                'latitude' => (float) $map->latitude,
                'longitude' => (float) $map->longitude,
                'title' => $map->{'title_'.$velocityLanguageCode} ?: $map->{'title_'.$velocityDefaultCode},
                'details' => $map->{'details_'.$velocityLanguageCode} ?: $map->{'details_'.$velocityDefaultCode},
            ])->values(),
        ];
    @endphp
    <section class="velocity-topic-section velocity-map-section" aria-labelledby="{{ $velocityMapId }}-title">
        <h2 id="{{ $velocityMapId }}-title">{{ __('frontend.locationMap') }}</h2>
        <div id="{{ $velocityMapId }}" class="velocity-map" data-velocity-map aria-hidden="true"></div>
        <script id="{{ $velocityMapId }}-config" type="application/json" data-velocity-map-config>@json($velocityMapConfig)</script>
        <ol class="velocity-map-locations">
            @foreach($velocityMapConfig['locations'] as $velocityLocation)
                <li>
                    <strong>{{ $velocityLocation['title'] }}</strong>
                    @if(filled($velocityLocation['details']))<span>{{ strip_tags($velocityLocation['details']) }}</span>@endif
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $velocityLocation['latitude'] }},{{ $velocityLocation['longitude'] }}" target="_blank" rel="noopener">
                        {{ __('backend.viewDetails') }}
                    </a>
                </li>
            @endforeach
        </ol>
    </section>
@endif

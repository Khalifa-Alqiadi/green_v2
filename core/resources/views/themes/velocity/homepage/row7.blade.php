@php
    $velocityPartners = Helper::Topics(Helper::GeneralWebmasterSettings('home_content3_section_id'), 0, 0, 1);
@endphp
@if($velocityPartners->isNotEmpty())
    <section id="partners" class="velocity-section velocity-section--muted" data-velocity-reveal>
        <div class="velocity-container">
            @include(theme_view('components.section-heading'), [
                'title' => __('frontend.partners'),
                'description' => __('frontend.partnersMsg'),
            ])
            <div class="velocity-partners-grid" role="list">
                @foreach($velocityPartners as $velocityPartner)
                    @php
                        $velocityTitle = $velocityPartner->{$title_var} ?: $velocityPartner->{$title_var2};
                        $velocityUrl = collect($velocityPartner->fields)
                            ->pluck('field_value')
                            ->first(fn ($value) => filter_var($value, FILTER_VALIDATE_URL));
                    @endphp
                    <div class="velocity-partners-grid__item" role="listitem">
                        @if($velocityUrl)
                            <a class="velocity-partners-grid__content" href="{{ $velocityUrl }}" target="_blank" rel="noopener noreferrer">
                        @else
                            <div class="velocity-partners-grid__content">
                        @endif
                            @if(filled($velocityPartner->photo_file))
                                <img src="{{ route('fileView', ['path' => 'topics/'.$velocityPartner->photo_file]) }}?w=320&h=180&r=fit" alt="{{ Helper::topicPhotoAlt($velocityPartner) }}" width="320" height="180" loading="lazy" decoding="async">
                            @else
                                <span>{{ $velocityTitle }}</span>
                            @endif
                        @if($velocityUrl)
                            </a>
                        @else
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

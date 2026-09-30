@php
    $velocityStaff = Helper::Topics(Helper::GeneralWebmasterSettings('home_content5_section_id'), 0, 6, 0);
    $velocitySectionUrl = $velocityStaff->isNotEmpty() ? Helper::sectionURL($velocityStaff->first()->webmaster_id) : null;
@endphp
@if($velocityStaff->isNotEmpty())
    <section id="staff" class="velocity-section" data-velocity-reveal>
        <div class="velocity-container velocity-section-layout">
            @include(theme_view('components.section-heading'), [
                'title' => __('frontend.homeStaffTitle'),
                'description' => __('frontend.homeStaffDesc'),
            ])
            <div class="velocity-people-grid">
                @foreach($velocityStaff as $velocityPerson)
                    @php
                        $velocityTitle = $velocityPerson->{$title_var} ?: $velocityPerson->{$title_var2};
                        $velocityDetailsField = filled($velocityPerson->{$details_var}) ? $details_var : $details_var2;
                        $velocitySummary = trim(strip_tags((string) $velocityPerson->{$velocityDetailsField}));
                        $velocityUrl = Helper::topicURL($velocityPerson->id, '', $velocityPerson);
                    @endphp
                    <article class="velocity-person velocity-panel" data-velocity-reveal>
                        @if(filled($velocityPerson->photo_file))
                            <a href="{{ $velocityUrl }}" class="velocity-person__media" tabindex="-1" aria-hidden="true">
                                <img src="{{ route('fileView', ['path' => 'topics/'.$velocityPerson->photo_file]) }}?w=360&h=420&r=fit" alt="{{ Helper::topicPhotoAlt($velocityPerson) }}" width="360" height="420" loading="lazy" decoding="async">
                            </a>
                        @endif
                        <div class="velocity-person__body">
                            <h3><a href="{{ $velocityUrl }}">{{ $velocityTitle }}</a></h3>
                            @if($velocitySummary !== '')
                                <p>{{ \Illuminate\Support\Str::limit($velocitySummary, 140) }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="velocity-section-actions">
                @include(theme_view('components.more-link'), ['url' => $velocitySectionUrl])
            </div>
        </div>
    </section>
@endif

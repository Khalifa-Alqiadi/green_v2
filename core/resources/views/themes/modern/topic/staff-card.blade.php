@php
    $staffTitleText = trim(strip_tags($title));
    $staffDetailsText = trim(strip_tags($Topic->$details));
@endphp

<div class="swiper-slide staff-grid-item">
    <article class="member">
        <div class="staff-member-media">
            <a href="{{ $topic_link_url }}" class="staff-member-photo"
               aria-label="{{ __('frontend.moreDetails') }}: {{ $staffTitleText }}">
                @if($Topic->photo_file != "")
                    <img loading="lazy"
                         src="{{ route('fileView',['path' => 'topics/'.$Topic->photo_file]) }}?w=720&h=900&r=fit"
                         width="720" height="900" alt="{{ Helper::topicPhotoAlt($Topic) }}"/>
                @else
                    <span class="staff-member-placeholder" role="img" aria-label="{{ $staffTitleText }}">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                    </span>
                @endif
            </a>

            <div class="member-info">
                <a href="{{ $topic_link_url }}" class="staff-member-arrow"
                   aria-label="{{ __('frontend.moreDetails') }}: {{ $staffTitleText }}">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </a>

                <a href="{{ $topic_link_url }}" class="staff-member-name">
                    <h3>{!! $title !!}</h3>
                </a>

                {{-- Additional Fields --}}
                @include(theme_view('topic.fields'),[
                    'cols' => 12,
                    'Fields' => @$Topic->webmasterSection->customFields->where('in_listing', true)
                ])

                @if($staffDetailsText != "")
                    <p class="staff-member-summary">
                        {!! mb_substr($staffDetailsText, 0, 140) !!}{{ mb_strlen($staffDetailsText) > 140 ? '...' : '' }}
                        <a href="{{ $topic_link_url }}">{{ __('frontend.moreDetails') }}</a>
                    </p>
                @endif
            </div>
        </div>
    </article>
</div>

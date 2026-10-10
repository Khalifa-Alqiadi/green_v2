@php
    extract(require resource_path('views/themes/modern/landing/_yh-data.php'));
@endphp

@if($yhTopics->isNotEmpty())
    <section id="landing-block-{{ $TopicBlock->id }}" class="yh-scholar-atlas {{ $TopicBlock->css_classes }}" aria-label="{{ $yhBlockTitle ?: __('yemen_home.scholars_title') }}">
        <div class="container">
            <header class="yh-scholar-atlas__head">
                <div>
                    <span class="yh-scholar-atlas__eyebrow"><i aria-hidden="true"></i>{{ __('yemen_home.scholars_eyebrow') }}</span>
                    @if($TopicBlock->title_status)
                        <h2>{{ $yhBlockTitle ?: __('yemen_home.scholars_title') }}</h2>
                    @endif
                    @if($TopicBlock->desc_status)
                        <p>{{ $yhBlockDescription ?: __('yemen_home.scholars_description') }}</p>
                    @endif
                </div>
                @if($TopicBlock->more_btn_status)
                    <a class="yh-scholar-atlas__all" href="{{ $yhSectionUrl }}">
                        {{ __('yemen_home.scholars_browse') }}
                        <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i>
                    </a>
                @endif
            </header>

            <div class="yh-scholar-atlas__search">
                <div class="yh-scholar-atlas__search-copy">
                    <span class="yh-scholar-atlas__search-kicker">{{ __('yemen_home.scholars_search_eyebrow') }}</span>
                    <h3>{{ __('yemen_home.scholars_search_title') }}</h3>
                    <p>{{ __('yemen_home.scholars_search_description') }}</p>
                </div>
                <form class="yh-scholar-atlas__form" method="GET" action="{{ $yhSectionUrl }}" role="search">
                    <label class="visually-hidden" for="yh-scholar-search-{{ $TopicBlock->id }}">{{ __('yemen_home.scholars_search_hint') }}</label>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="yh-scholar-search-{{ $TopicBlock->id }}" type="search" name="search_word" maxlength="80" required
                           placeholder="{{ __('yemen_home.scholars_search_hint') }}" value="{{ request('search_word') }}">
                    <button type="submit">{{ __('yemen_home.scholars_search_title') }} <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i></button>
                </form>
            </div>

            <div class="yh-scholar-atlas__index-head">
                <div>
                    <span>{{ __('yemen_home.scholars_eyebrow') }}</span>
                    <h3>{{ __('yemen_home.scholars_biography') }}</h3>
                </div>
                <span class="yh-scholar-atlas__count" aria-hidden="true">{{ str_pad((string) $yhTopics->take(4)->count(), 2, '0', STR_PAD_LEFT) }}</span>
            </div>

            <div class="yh-scholar-atlas__grid">
                @foreach($yhTopics->take(4) as $item)
                    @php
                        $scholarName = $yhTopicTitle($item);
                        $nameWithoutPrefix = trim(str_replace(__('yemen_home.scholars_name_prefixes'), '', $scholarName));
                        $initial = mb_substr($nameWithoutPrefix ?: $scholarName, 0, 1);
                    @endphp
                    <a class="yh-scholar-atlas__card" href="{{ Helper::topicURL($item->id, '', $item) }}">
                        <span class="yh-scholar-atlas__portrait">
                            @if($item->photo_file)
                                <img src="{{ route('fileView', ['path' => 'topics/'.$item->photo_file]) }}" alt="" loading="lazy" decoding="async">
                            @else
                                <b aria-hidden="true">{{ $initial }}</b>
                            @endif
                        </span>
                        <span class="yh-scholar-atlas__card-copy">
                            <small>{{ __('yemen_home.scholars_biography') }}</small>
                            <strong>{{ $scholarName }}</strong>
                            @if($yhTopicDetails($item))
                                <em>{!! str_replace('"', " ", \Illuminate\Support\Str::limit(strip_tags($yhTopicDetails($item)), 100)) !!}</em>
                            @endif
                        </span>
                        <span class="yh-scholar-atlas__card-foot">
                            <span>{{ __('yemen_home.scholars_view_profile') }}</span>
                            <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

<aside class="velocity-sidebar" aria-label="{{ __('frontend.categories') }}">
    <form
        method="GET"
        action="{{ Helper::sectionURL($WebmasterSection->id, '', $WebmasterSection) }}"
        class="velocity-search-form"
        id="side-search-form"
        role="search"
    >
        <label class="velocity-visually-hidden" for="velocity-side-search">{{ __('frontend.search') }}</label>
        <input
            class="velocity-field"
            type="search"
            name="search_word"
            id="velocity-side-search"
            value="{{ $search_word ?? '' }}"
            required
            maxlength="50"
            autocomplete="off"
            placeholder="{{ __('frontend.search') }}"
        >
        <button class="velocity-icon-button" type="submit" aria-label="{{ __('backend.search') }}">
            @include(theme_view('components.icon'), ['name' => 'search'])
        </button>
    </form>

    @if(collect($Categories ?? [])->isNotEmpty())
        <section class="velocity-sidebar__widget velocity-panel">
            <h2 class="velocity-sidebar__title">{{ __('frontend.categories') }}</h2>
            @include(theme_view('components.category-tree'), [
                'categories' => $Categories,
                'currentCategory' => $CurrentCategory ?? null,
                'counts' => $TopicsCountPerCat ?? [],
            ])
        </section>
    @endif

    @if(collect($MostViewedTopics ?? [])->isNotEmpty() && View::exists(theme_view('topic.card2')))
        <section class="velocity-sidebar__widget p-0">
            <h2 class="velocity-sidebar__title">{{ __('frontend.mostViewed') }}</h2>
            <div class="velocity-stack">
                @foreach($MostViewedTopics as $mostViewedTopic)
                    @include(theme_view('topic.card2'), ['Post' => $mostViewedTopic])
                @endforeach
            </div>
        </section>
    @endif

    @includeIf(theme_view('layouts.banners'), [
        'BannersSettingsId' => Helper::GeneralWebmasterSettings('side_banners_section_id'),
    ])
</aside>

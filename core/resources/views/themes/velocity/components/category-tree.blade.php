@php
    $languageCode = Helper::currentLanguage()->code;
    $defaultLanguage = config('smartend.default_language');
    $depth = (int) ($depth ?? 0);
@endphp

<ul class="velocity-category-list {{ $depth > 0 ? 'velocity-category-list--nested' : 'velocity-category-list--root' }}" data-category-depth="{{ $depth }}">
    @foreach($categories as $category)
        @php
            $title = $category->{'title_'.$languageCode} ?: $category->{'title_'.$defaultLanguage};
            $children = collect($category->fatherSections ?? []);
            $isActive = $currentCategory && $currentCategory !== 'none' && $category->id === $currentCategory->id;
        @endphp
        <li class="velocity-category-list__item {{ $children->isNotEmpty() ? 'velocity-category-list__item--parent' : '' }}">
            <a
                class="velocity-category-list__link {{ $isActive ? 'active' : '' }}"
                href="{{ Helper::categoryURL($category->id, '', $category) }}"
                @if($isActive) aria-current="page" @endif
            >
                <span class="velocity-category-list__copy">
                    <span class="velocity-category-list__icon" aria-hidden="true">
                    @if($category->icon)
                            <i class="{{ $category->icon }}"></i>
                        @else
                            <span class="velocity-category-list__marker"></span>
                    @endif
                    </span>
                    <span class="velocity-category-list__label">{{ $title }}</span>
                </span>
                <span class="velocity-category-list__trailing">
                    @if(($counts[$category->id] ?? 0) > 0)
                        <span class="velocity-badge">{{ $counts[$category->id] }}</span>
                    @endif
                </span>
            </a>
            @if($children->isNotEmpty())
                @include(theme_view('components.category-tree'), [
                    'categories' => $children,
                    'currentCategory' => $currentCategory,
                    'counts' => $counts,
                    'depth' => $depth + 1,
                ])
            @endif
        </li>
    @endforeach
</ul>

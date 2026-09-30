@foreach($links as $index => $link)
    @php
        $children = collect($link->sub ?? []);
        $itemPath = $path.'-'.$index;
        $submenuId = 'velocity-submenu-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $itemPath);
        $sectionActiveClass = \App\Helpers\SiteMenu::ActiveLink(url()->current(), $link, $WebmasterSection ?? null);
        $currentUrl = rtrim(urldecode(url()->current()), '/');
        $linkUrl = rtrim(urldecode((string) ($link->url === '/' ? url('/') : $link->url)), '/');
        $isCurrentPage = filled($linkUrl) && $currentUrl === $linkUrl;
        $activeClass = $level === 1 ? $sectionActiveClass : ($isCurrentPage ? 'active' : '');
        $canExpand = $children->isNotEmpty() && $level < 4;
    @endphp
    <li
        class="velocity-menu__item velocity-menu__item--level-{{ $level }}"
        data-velocity-menu-item
        data-velocity-menu-level="{{ $level }}"
        @if($canExpand) data-velocity-menu-parent @endif
    >
        <div class="velocity-menu__row">
            <a
                class="velocity-menu__link {{ $activeClass }}"
                href="{{ $link->url ?: '#' }}"
                target="{{ $link->target ?: '_self' }}"
                @if($isCurrentPage) aria-current="page" @endif
            >
                @if($link->icon)
                    <span class="{{ $link->icon }}" aria-hidden="true"></span>
                @endif
                <span>{{ $link->title }}</span>
            </a>
            @if($canExpand)
                <button
                    class="velocity-menu__toggle"
                    type="button"
                    aria-expanded="false"
                    aria-controls="{{ $submenuId }}"
                    aria-haspopup="true"
                    aria-label="{{ $link->title }}"
                    data-velocity-submenu-toggle
                >
                    @include(theme_view('components.icon'), ['name' => 'chevron-down'])
                </button>
            @endif
        </div>
        @if($canExpand)
            <ul
                class="velocity-menu__submenu"
                id="{{ $submenuId }}"
                data-velocity-menu-panel
                data-velocity-menu-label="{{ $link->title }}"
                data-velocity-menu-level="{{ $level + 1 }}"
                hidden
            >
                @include(theme_view('components.menu-items'), [
                    'links' => $children,
                    'level' => $level + 1,
                    'path' => $itemPath,
                    'WebmasterSection' => $WebmasterSection ?? null,
                ])
            </ul>
        @endif
    </li>
@endforeach

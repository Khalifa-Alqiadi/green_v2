@php
    try {
        $velocitySocialLinks = json_decode((string) Helper::GeneralSiteSettings('social_links'), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $velocitySocialLinks = [];
    }

    $velocitySocialLinks = collect(is_array($velocitySocialLinks) ? $velocitySocialLinks : [])
        ->filter(static fn ($link) => is_array($link)
            && trim((string) ($link['title'] ?? '')) !== ''
            && (trim((string) ($link['url'] ?? '')) === '#'
                || in_array(strtolower((string) parse_url(trim((string) ($link['url'] ?? '')), PHP_URL_SCHEME)), ['http', 'https'], true)));
@endphp

@if($velocitySocialLinks->isNotEmpty())
    <ul class="velocity-social" aria-label="{{ __('frontend.followUs') }}">
        @foreach($velocitySocialLinks as $link)
            @php
                $socialUrl = trim((string) ($link['url'] ?? ''));
                $socialPlaceholder = $socialUrl === '#';
                $socialColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($link['color'] ?? '')) === 1
                    ? $link['color']
                    : null;
                $socialTitle = trim((string) ($link['title'] ?? ''));
                $socialIcon = null;
                $socialIconClasses = preg_split('/\s+/', strtolower(trim((string) ($link['icon'] ?? ''))));
                $socialNetworkNames = ['facebook', 'instagram', 'youtube', 'linkedin', 'twitter', 'telegram', 'whatsapp'];
                foreach ($socialNetworkNames as $network) {
                    if (in_array('fa-'.$network, $socialIconClasses, true)
                        || in_array('fa-'.$network.'-in', $socialIconClasses, true)
                        || in_array('fa-'.$network.'-f', $socialIconClasses, true)
                        || strtolower($socialTitle) === $network) {
                        $socialIcon = $network;
                        break;
                    }
                }
                if (in_array('fa-x-twitter', $socialIconClasses, true) || in_array(strtolower($socialTitle), ['x', 'x.com'], true)) {
                    $socialIcon = 'x';
                }
                $socialContrast = '#FFFFFF';
                if ($socialColor) {
                    $socialChannels = array_map(static function ($offset) use ($socialColor) {
                        $channel = hexdec(substr($socialColor, $offset, 2)) / 255;
                        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
                    }, [1, 3, 5]);
                    $socialLuminance = 0.2126 * $socialChannels[0] + 0.7152 * $socialChannels[1] + 0.0722 * $socialChannels[2];
                    $socialContrast = 1.05 / ($socialLuminance + 0.05) >= ($socialIcon ? 3 : 4.5) ? '#FFFFFF' : '#111827';
                }
            @endphp
            <li>
                <a
                    class="velocity-social__link"
                    title="{{ $socialTitle }}"
                    aria-label="{{ $socialTitle }}"
                        href="{{ $socialUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"

                    @if($socialColor) style="--velocity-social-color: {{ $socialColor }}; --velocity-social-contrast: {{ $socialContrast }}" @endif
                >
                    @if($socialIcon)
                        @include(theme_view('components.social-icon'), ['name' => $socialIcon])
                    @else
                        <span aria-hidden="true">{{ mb_strtoupper(mb_substr($socialTitle, 0, 2)) }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endif

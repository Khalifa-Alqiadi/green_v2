@if(Helper::GeneralWebmasterSettings('popups_status') && !empty($Popup))
    @php
        $popupLanguage = Helper::currentLanguage()->code;
        $popupDefaultLanguage = config('smartend.default_language');
        $popupTitle = $Popup->{'title_'.$popupLanguage} ?: $Popup->{'title_'.$popupDefaultLanguage};
        $popupDetails = $Popup->{'details_'.$popupLanguage} ?: $Popup->{'details_'.$popupDefaultLanguage};

        try {
            $popupSettings = json_decode((string) $Popup->settings, false, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $popupSettings = (object) [];
        }

        $popupLastView = (int) Cookie::get('page-popup-'.$Popup->id.'-last-view-date');
        $popupPeriod = (int) ($popupSettings->period ?? 0);
        $showPopup = $popupLastView === 0
            || $popupPeriod === -1
            || ($popupPeriod > 0 && strtotime('+'.$popupPeriod.' days', $popupLastView) <= time());
        $popupClosable = (bool) ($popupSettings->closable ?? true);
        $popupDelay = max(0, (int) ($popupSettings->delay ?? 0)) * 1000;
        $popupWidth = min(880, max(320, (int) ($popupSettings->width ?? 640)));
        $popupBackdrop = min(95, max(0, (int) ($popupSettings->backdrop_opacity ?? 72))) / 100;
        $popupBackground = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($popupSettings->background_color ?? '')) === 1
            ? $popupSettings->background_color
            : null;
    @endphp

    @if($showPopup)
        @php
            Cookie::queue('page-popup-'.$Popup->id.'-last-view-date', time(), 525600);
        @endphp
        <dialog
            class="velocity-popup"
            id="page-popup-{{ $Popup->id }}"
            aria-label="{{ $popupTitle }}"
            tabindex="-1"
            data-velocity-popup
            data-delay="{{ $popupDelay }}"
            data-closable="{{ $popupClosable ? 'true' : 'false' }}"
            open
            style="
                width: min({{ $popupWidth }}px, calc(100% - 2rem));
                --velocity-popup-backdrop: {{ $popupBackdrop }};
                @if($popupBackground) background-color: {{ $popupBackground }}; @endif
            "
        >
            @if($popupClosable)
                <form method="dialog">
                    <button class="velocity-icon-button velocity-popup__close" type="submit" aria-label="{{ __('backend.close') }}">
                        @include(theme_view('components.icon'), ['name' => 'close'])
                    </button>
                </form>
            @endif

            @if($Popup->photo && ($popupSettings->photo_position ?? false))
                <div class="velocity-popup__grid">
                    <div class="velocity-popup__media-frame">
                        <img
                            class="velocity-popup__media"
                            src="{{ route('fileView', ['path' => 'banners/'.$Popup->photo]) }}?w=800&h=900&r=fit"
                            alt="{{ $popupTitle }}"
                            width="800"
                            height="900"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                    <div class="velocity-popup__content velocity-richtext">
                        @if(strip_tags((string) $popupDetails) !== '')
                            {!! $popupDetails !!}
                        @endif
                        {!! $Popup->code !!}
                        @if($Popup->form_id == -1)
                            @include(theme_view('layouts.popup_subscribe'), ['PopupID' => $Popup->id])
                        @elseif($Popup->form_id > 0)
                            @includeIf(theme_view('form'), ['FormSectionID' => $Popup->form_id])
                        @endif
                    </div>
                </div>
            @else
                <div
                    class="velocity-popup__content velocity-richtext"
                    @if($Popup->photo)
                        style="background-image: linear-gradient(rgb(255 255 255 / .88), rgb(255 255 255 / .94)), url('{{ route('fileView', ['path' => 'banners/'.$Popup->photo]) }}'); background-position: center; background-size: cover;"
                    @endif
                >
                    @if(strip_tags((string) $popupDetails) !== '')
                        {!! $popupDetails !!}
                    @endif
                    {!! $Popup->code !!}
                    @if($Popup->form_id == -1)
                        @include(theme_view('layouts.popup_subscribe'), ['PopupID' => $Popup->id])
                    @elseif($Popup->form_id > 0)
                        @includeIf(theme_view('form'), ['FormSectionID' => $Popup->form_id])
                    @endif
                </div>
            @endif
        </dialog>
    @endif
@endif

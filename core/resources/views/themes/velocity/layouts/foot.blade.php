@if(Helper::GeneralWebmasterSettings('google_tags_status'))
    @php
        $tagManagerId = trim((string) Helper::GeneralWebmasterSettings('google_tags_id'));
    @endphp
    @if(preg_match('/^GTM-[A-Z0-9]+$/i', $tagManagerId))
        <noscript>
            <iframe
                src="https://www.googletagmanager.com/ns.html?id={{ $tagManagerId }}"
                height="0"
                width="0"
                title="Google Tag Manager"
                style="display:none;visibility:hidden"
            ></iframe>
        </noscript>
    @endif
@endif

@include(theme_view('layouts.cookie'))

@php
    $velocityVisitorTitle = trim((string) ($PageTitle ?? ''))
        ?: Helper::GeneralSiteSettings('site_title_'.Helper::currentLanguage()->code);
@endphp
{!! Helper::SaveVisitorInfo($velocityVisitorTitle) !!}

<div
    class="tab-pane {{ ( Session::get('active_tab') == 'infoTab') ? 'active' : '' }}"
    id="tab-1">
    <div class="p-a-md"><h5><i class="material-icons">&#xe30c;</i>
            &nbsp; {!!  __('backend.siteInfoSettings') !!}</h5></div>
    <div class="p-a-md col-md-12">
        @foreach(Helper::languagesList() as $ActiveLanguage)
            <div class="form-group">
                <label for="site_title_{{ @$ActiveLanguage->code }}">{!!  __('backend.websiteTitle') !!}  {!! @Helper::languageName($ActiveLanguage) !!}</label>
                <div class="input-group">
                    <input type="text" autocomplete="off" name="site_title_{{ @$ActiveLanguage->code }}" id="site_title_{{ @$ActiveLanguage->code }}" value="{{ $Setting->{'site_title_'.@$ActiveLanguage->code} }}" dir="{{ @$ActiveLanguage->direction }}" class="form-control" maxlength="191" required/>
                    @if($ActiveLanguage->box_status && (($aiCapabilities['seo'] ?? false) || ($aiCapabilities['translation'] ?? false)))
                        <div class="input-group-btn dropdown smartend-ai-tools">
                            <button type="button" class="btn white dropdown-toggle" data-toggle="dropdown" title="{{ __('backend.aiAssistant') }}" aria-label="{{ __('backend.aiAssistant') }}"><i class="material-icons" aria-hidden="true">&#xe39f;</i><span class="caret"></span></button>
                            <div class="dropdown-menu pull-right">
                                <div class="smartend-ai-menu-heading"><i class="material-icons" aria-hidden="true">&#xe39f;</i>{{ __('backend.aiAssistant') }}</div>
                                @if($aiCapabilities['seo'] ?? false)<button type="button" class="dropdown-item smartend-ai-seo" data-language="{{ $ActiveLanguage->code }}"><i class="material-icons">trending_up</i><span>{{ __('backend.aiImproveSeo') }}</span></button>@endif
                                @if(($aiCapabilities['seo'] ?? false) && ($aiCapabilities['translation'] ?? false))<div class="divider"></div>@endif
                                @if($aiCapabilities['translation'] ?? false)
                                    <button type="button" class="dropdown-item smartend-ai-seo-translate" data-language="{{ $ActiveLanguage->code }}"><i class="material-icons">translate</i><span>{{ __('backend.aiTranslateSeo') }}</span></button>
                                    <button type="button" class="dropdown-item smartend-ai-seo-translate-all" data-language="{{ $ActiveLanguage->code }}"><i class="material-icons">language</i><span>{{ __('backend.aiTranslateMissing') }}</span></button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        @foreach(Helper::languagesList() as $ActiveLanguage)
            <div class="form-group">
                <label for="site_desc_{{ @$ActiveLanguage->code }}">{!!  __('backend.metaDescription') !!} {!! @Helper::languageName($ActiveLanguage) !!}</label>
                <textarea name="site_desc_{{ @$ActiveLanguage->code }}" id="site_desc_{{ @$ActiveLanguage->code }}" class="form-control" dir="{{ @$ActiveLanguage->direction }}" rows="2"> {{ $Setting->{'site_desc_'.@$ActiveLanguage->code} }}</textarea>
            </div>
        @endforeach
        @foreach(Helper::languagesList() as $ActiveLanguage)
            <div class="form-group">
                <label for="site_keywords_{{ @$ActiveLanguage->code }}">{!!  __('backend.metaKeywords') !!} {!! @Helper::languageName($ActiveLanguage) !!}</label>
                <textarea name="site_keywords_{{ @$ActiveLanguage->code }}" id="site_keywords_{{ @$ActiveLanguage->code }}" class="form-control" dir="{{ @$ActiveLanguage->direction }}" rows="2"> {{ $Setting->{'site_keywords_'.@$ActiveLanguage->code} }}</textarea>
            </div>
        @endforeach
        <div class="form-group">
            <label id="site_url">{!!  __('backend.websiteUrl') !!}</label>
            <input type="text" autocomplete="off" name="site_url" id="site_url" value="{{ $Setting->site_url }}" placeholder="http//:www.sitename.com/"
                   dir="ltr" class="form-control"/>
        </div>
    </div>

</div>

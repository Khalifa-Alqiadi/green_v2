<div class="modal fade" id="website-settings" data-backdrop="true">
    <div class="right w-xl white b-r switcher">
        <div class="row-col"><a data-dismiss="modal" class="pull-right text-muted text-lg p-a-sm m-r-sm">×</a>
            <div class="p-a b-b"><span class="h6"><i class="material-icons">&#xe8b8;</i> {{ __('backend.themeSwitcher') }}</span>
            </div>
            <div class="row-row">
                <div class="row-body">
                    <div class="row-inner">
                        <div class="p-a-2">
                            <p class="m-b-sm h6">{{ __('backend.themes') }}</p>
                            <div data-target="bg" class="text-u-c text-center _600 clearfix">
                                <label class="p-a col-xs-6 light pointer m-a-0">
                                    <input type="radio" name="theme" value="" hidden>
                                    {{ __('backend.themes1') }}
                                </label>
                                <label class="p-a col-xs-6 grey pointer m-a-0">
                                    <input type="radio" name="theme" value="grey" hidden>
                                    {{ __('backend.themes2') }}
                                </label>
                                <label class="p-a col-xs-6 dark pointer m-a-0" style="background: #2e3e4e">
                                    <input type="radio" name="theme" value="dark" hidden>
                                    {{ __('backend.themes3') }}
                                </label>
                                <label class="p-a col-xs-6 black pointer m-a-0">
                                    <input type="radio" name="theme" value="black" hidden>
                                    {{ __('backend.themes4') }}
                                </label>
                            </div>
                            <br>
                            @if(count(Helper::languagesList()) >0)
                                <p class="m-b h6">{{ __('backend.language') }}</p>
                                <div>
                                    @foreach(Helper::languagesList() as $ActiveLanguage)
                                        <div>
                                            <a href="{{ route("localeChange",$ActiveLanguage->code) }}"
                                               class="btn light btn-block m-b-xs text-left p-x-1">
                                                @if($ActiveLanguage->icon !="")
                                                    <img
                                                        src="{{ asset('assets/dashboard/images/flags/'.$ActiveLanguage->icon.".svg") }}"
                                                        alt="" class="w-20">
                                                @endif
                                                {{ $ActiveLanguage->title }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-a light b-t">
                <button class="btn dark btn-block" data-target="#cash-clear" data-toggle="modal"><small>{!!  __('backend.cashClear') !!}</small></button>
            </div>
        </div>
    </div>
</div>
<div id="cash-clear" class="modal fade" data-backdrop="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('backend.confirmation') }}</h5>
            </div>
            <div class="modal-body text-center p-lg">
                <p class="h5">{{ __('backend.cashClearMsg') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn dark-white p-x-md" data-dismiss="modal">{{ __('backend.no') }}</button>
                <a type="button" class="btn danger p-x-md" href="{{ route('cacheClear') }}">{{ __('backend.yes') }}</a>
            </div>
        </div><!-- /.modal-content -->
    </div>
</div>

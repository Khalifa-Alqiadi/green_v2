<?php
$style_color1 = Helper::GeneralSiteSettings("style_color1") ?: "#0cbaa4";
$style_color2 = Helper::GeneralSiteSettings("style_color2") ?: "#2e3e4e";
$site_title_var = "site_title_".@Helper::currentLanguage()->code;
$site_desc_var = "site_desc_".@Helper::currentLanguage()->code;
$site_logo_var = "style_logo_".@Helper::currentLanguage()->code;
$footer_logo = Helper::GeneralSiteSettings('style_footer_logo') ?: Helper::GeneralSiteSettings($site_logo_var);
$contact_address = Helper::GeneralSiteSettings("contact_t1_" . @Helper::currentLanguage()->code);
$contact_phone = Helper::GeneralSiteSettings("contact_t3");
$contact_email = Helper::GeneralSiteSettings("contact_t6");
?>
<footer id="footer" class="modern-footer">
    @if(Helper::GeneralSiteSettings("style_footer")==1)
        <div class="footer-top">
            <div class="container" >
                @if($contact_address !="" || $contact_phone !="" || $contact_email !="")
                    <div class="modern-footer-contact-bar">
                        @if($contact_address !="")
                            <div class="modern-footer-contact-item">
                                <span class="modern-footer-contact-icon"><i class="bi bi-geo-alt"></i></span>
                                <span>
                                    <span class="modern-footer-contact-label">{{ __('frontend.address') }}</span>
                                    <span class="modern-footer-contact-value">{{ $contact_address }}</span>
                                </span>
                            </div>
                        @endif
                        @if($contact_phone !="")
                            <div class="modern-footer-contact-item">
                                <span class="modern-footer-contact-icon"><i class="bi bi-telephone"></i></span>
                                <span>
                                    <span class="modern-footer-contact-label">{{ __('frontend.callUs') }}</span>
                                    <a class="modern-footer-contact-value" href="tel:{{ $contact_phone }}"><span dir="ltr">{{ $contact_phone }}</span></a>
                                </span>
                            </div>
                        @endif
                        @if($contact_email !="")
                            <div class="modern-footer-contact-item">
                                <span class="modern-footer-contact-icon"><i class="bi bi-envelope"></i></span>
                                <span>
                                    <span class="modern-footer-contact-label">{{ __('frontend.email') }}</span>
                                    <a class="modern-footer-contact-value" href="mailto:{{ $contact_email }}">{{ $contact_email }}</a>
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="row g-4 modern-footer-main">
                    <div class="col-lg-4 col-md-12">
                        <div class="modern-footer-brand">
                            <a class="modern-footer-logo" href="{{ Helper::homeURL() }}">
                                @if($footer_logo)
                                    <img alt="{{ Helper::GeneralSiteSettings($site_title_var) }}"
                                         src="{{ route("fileView",["path" =>'settings/'.$footer_logo ]) }}" class="img-fluid" width="230" height="50">
                                @else
                                    <img alt="{{ Helper::GeneralSiteSettings($site_title_var) }}" src="{{ route("fileView",["path" =>'settings/nologo.png' ]) }}" class="img-fluid" width="172" height="50">
                                @endif
                            </a>
                            @if(Helper::GeneralSiteSettings($site_desc_var) !="")
                                <p class="modern-footer-description">{{ Helper::GeneralSiteSettings($site_desc_var) }}</p>
                            @endif
                            @include(theme_view("layouts.social"),["tt_position"=>"top"])
                        </div>
                    </div>

                    @if(Helper::GeneralWebmasterSettings("footer_menu_id") >0)
                            <?php
                            // Get list of footer menu links by group Id
                            $MenuLinks = \App\Helpers\SiteMenu::List(Helper::GeneralWebmasterSettings("footer_menu_id"));
                            $max_menu_cols = Helper::GeneralSiteSettings("style_subscribe") ? 2 : 3;
                            $mi = 0;
                            ?>
                        @if(count($MenuLinks) <= $max_menu_cols)
                            @foreach($MenuLinks as $MenuLink)
                                <div class="col-lg-2 col-md-6 footer-links">
                                    <div class="footer-title">
                                        <h3>{{ @$MenuLink->title }}</h3>
                                    </div>
                                    @if(@$MenuLink->sub)
                                        <ul>
                                            @foreach($MenuLink->sub as $SubLink)
                                                <li><a class="nav-link" href="{{ @$SubLink->url }}"
                                                       target="{{ @$SubLink->target }}">{!! (@$SubLink->icon)?"<i class='".@$SubLink->icon."'></i> ":"" !!} {{ @$SubLink->title }}
                                                    </a>
                                                </li>
                                                @if(@$SubLink->sub)
                                                    @foreach($SubLink->sub as $SubLink2)
                                                        <li><a
                                                                class="nav-link"
                                                                href="{{ @$SubLink2->url }}"
                                                                target="{{ @$SubLink2->target }}">
                                                                &nbsp;&nbsp; {!! (@Helper::currentLanguage()->direction=="rtl")?"&#8617;":"&#8618;" !!} {!! (@$SubLink2->icon)?"<i class='".@$SubLink2->icon."'></i> ":"" !!} {{ @$SubLink2->title }}</a>
                                                        </li>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                @php($mi++)
                            @endforeach
                        @elseif(count($MenuLinks) > $max_menu_cols)
                            <div class="col-lg-2 col-md-6 footer-links">
                                <div class="footer-title">
                                    <h3>{{ __('frontend.quickLinks') }}</h3>
                                </div>
                                <ul>
                                    @foreach($MenuLinks as $MenuLink)
                                        <li><a class="nav-link" href="{{ @$MenuLink->url }}"
                                               target="{{ @$MenuLink->target }}">{!! (@$MenuLink->icon)?"<i class='".@$MenuLink->icon."'></i> ":"" !!} {{ @$MenuLink->title }}
                                            </a>
                                        </li>
                                        @if(@$MenuLink->sub)
                                            @foreach($MenuLink->sub as $SubLink)
                                                <li><a
                                                        class="nav-link"
                                                        href="{{ @$SubLink->url }}"
                                                        target="{{ @$SubLink->target }}">
                                                        &nbsp;&nbsp; {!! (@Helper::currentLanguage()->direction=="rtl")?"&#8617;":"&#8618;" !!} {!! (@$SubLink->icon)?"<i class='".@$SubLink->icon."'></i> ":"" !!} {{ @$SubLink->title }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                    @include(theme_view('layouts.subscribe'))
                </div>
            </div>
        </div>
    @endif
    <div class="footer-bottom">
        <div class="container py-4">
            <div class="text-center">
                <div class="copyright">
                    &copy; <?php echo date("Y") ?> {{ __('frontend.AllRightsReserved') }}
                    . <a href="#">{{Helper::GeneralSiteSettings($site_title_var)}}</a>
                </div>
                <div class="credits">
                    {{ __('frontend.poweredByName') }} <a href="https://yemenhosting.com">{{__('frontend.yemenHosting')}}</a>
                </div>
            </div>
        </div>
    </div>
</footer>
@if(Helper::GeneralSiteSettings('whatsapp_no') !="")
    <a href="https://wa.me/{{Helper::GeneralSiteSettings('whatsapp_no')}}" class="whatsapp_float" target="_blank" aria-label="Whatsapp"
       rel="noopener noreferrer">
        <i class="fa fa-whatsapp"></i>
    </a>
@endif
@if (@Auth::check())
    @if(!Helper::GeneralSiteSettings("site_status"))
        <div class="text-center alert alert-warning m-0">
            <div class="h6 mb-0">
                {{__('backend.websiteClosedForVisitors')}}
            </div>
        </div>
    @endif
@endif

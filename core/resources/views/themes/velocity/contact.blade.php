@extends(theme_view('layouts.master'))

@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityTitle = $Topic->{'title_'.$velocityLanguageCode} ?: $Topic->{'title_'.$velocityDefaultCode};
    $velocityDetailsField = filled($Topic->{'details_'.$velocityLanguageCode})
        ? 'details_'.$velocityLanguageCode
        : 'details_'.$velocityDefaultCode;
    $velocitySectionTitle = $WebmasterSection->{'title_'.$velocityLanguageCode}
        ?: $WebmasterSection->{'title_'.$velocityDefaultCode};
    $velocityCustomCss = (string) $WebmasterSection->css_code.(string) $Topic->css_code;
    $velocityCustomJs = (string) $WebmasterSection->js_code.(string) $Topic->js_code;
    $velocityCustomBody = (string) $WebmasterSection->body_code.(string) $Topic->body_code;
    $velocityHeadingFile = $Topic->photo_file ?: $WebmasterSection->photo;
    $velocityHeadingMedia = $velocityHeadingFile
        ? route('fileView', ['path' => 'topics/'.$velocityHeadingFile])
        : null;
@endphp

@section('content')
    @include(theme_view('components.page-heading'), [
        'title' => $velocityTitle,
        'breadcrumbs' => [
            ['label' => $velocitySectionTitle, 'url' => Helper::sectionURL($WebmasterSection->id, '', $WebmasterSection)],
            ['label' => $velocityTitle],
        ],
        'eyebrow' => $velocitySectionTitle,
        'variant' => $velocityHeadingMedia ? 'media' : 'gradient',
        'media' => $velocityHeadingMedia,
    ])

    <section class="velocity-section contact-page" id="content">
        <div class="velocity-container">

            @if (@$WebmasterSection->type == 10)
                @if($Topic->topicBlocks->where("status",1)->count() >0)
                    @foreach($Topic->topicBlocks->where("status",1) as $TopicBlock)
                        @if ($TopicBlock->content != "")
                                <?php
                                try {
                                    $TopicBlockContents = json_decode($TopicBlock->content);
                                } catch (Exception $e) {
                                    $TopicBlockContents = [];
                                }
                                ?>
                            @if($TopicBlock->type == 4)
                                {{--Form--}}
                                @if(@$TopicBlockContents->view_style !="")
                                    @if(@$TopicBlockContents->module_id >0)
                                        @include(theme_view('landing.').strtolower(str_replace(" ","",@$TopicBlockContents->view_style)))
                                    @else
                                        @include(theme_view('landing.contact'))
                                    @endif
                                @endif
                            @endif
                            @if($TopicBlock->type == 3)
                                {{--Dynamic--}}
                                @if(@$TopicBlockContents->view_style !="")
                                    @include(theme_view('landing.').strtolower(str_replace(" ","",@$TopicBlockContents->view_style)))
                                @endif
                            @elseif($TopicBlock->type == 2)
                                {{--Banners--}}
                                @if(@$TopicBlockContents->banner_area_id >0 && @$TopicBlockContents->banner_style !="")
                                    @include(theme_view('layouts.').@$TopicBlockContents->banner_style,["BannersSettingsId"=>@$TopicBlockContents->banner_area_id])
                                @endif
                            @elseif($TopicBlock->type == 1)
                                {{--Custom Code--}}
                                @include(theme_view('landing.code'))
                            @elseif($TopicBlock->type == 0)
                                {{--Static Content--}}
                                @include(theme_view('landing.static'))
                            @endif
                        @endif
                    @endforeach
                @endif
            @endif

            @if (@$WebmasterSection->type == 0)

                <article class="mb-5">
                    @if($WebmasterSection->type==2 && $Topic->video_file!="")
                        {{--video--}}
                        <div class="post-video">
                            <div class="video-container">
                                @if($Topic->video_type ==1)
                                        <?php
                                        $Youtube_id = Helper::Get_youtube_video_id($Topic->video_file);
                                        ?>
                                    @if($Youtube_id !="")
                                        {{-- Youtube Video --}}
                                        <iframe allowfullscreen
                                                src="https://www.youtube.com/embed/{{ $Youtube_id }}">
                                        </iframe>
                                    @endif
                                @elseif($Topic->video_type ==2)
                                        <?php
                                        $Vimeo_id = Helper::Get_vimeo_video_id($Topic->video_file);
                                        ?>
                                    @if($Vimeo_id !="")
                                        {{-- Vimeo Video --}}
                                        <iframe allowfullscreen
                                                src="https://player.vimeo.com/video/{{ $Vimeo_id }}?title=0&amp;byline=0">
                                        </iframe>
                                    @endif

                                @else
                                    <video width="100%" height="300" controls>
                                        <source src="{{ route("fileView",["path" =>'topics/'.$Topic->video_file ]) }}"
                                                type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                @endif


                            </div>
                        </div>
                    @elseif($WebmasterSection->type==3 && $Topic->audio_file!="")
                        {{--audio--}}
                        <div class="post-video">
                            <div class="video-container">
                                <audio controls>
                                    <source src="{{ route("fileView",["path" =>'topics/'.$Topic->audio_file ]) }}"
                                            type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>

                            </div>
                        </div>

                    @elseif(count($Topic->photos)>0)
                        {{--photo slider--}}
                        <div class="post-slider">
                            <!-- start flexslider -->
                            <div id="post-slider" class="flexslider">
                                <ul class="slides">
                                    @if($Topic->photo_file !="")
                                        <li>
                                            <img src="{{ route("fileView",["path" =>'topics/'.$Topic->photo_file ]) }}"
                                                 alt="{{ Helper::topicPhotoAlt($Topic) }}"/>
                                        </li>
                                    @endif
                                    @foreach($Topic->photos as $photo)
                                        <li>
                                            <img src="{{ route("fileView",["path" =>'topics/'.$photo->file ]) }}"
                                                 alt="{{ $photo->title  }}"/>
                                        </li>
                                    @endforeach

                                </ul>
                            </div>
                            <!-- end flexslider -->
                        </div>

                    @else
                        {{--one photo--}}
                        <div class="post-image">
                            @if($Topic->photo_file !="")
                                <img src="{{ route("fileView",["path" =>'topics/'.$Topic->photo_file ]) }}"
                                     alt="{{ Helper::topicPhotoAlt($Topic) }}"/>
                            @endif
                        </div>
                    @endif

                    {!! $Topic->$details !!}
                    @if($Topic->attach_file !="")
                            <?php
                            $file_ext = strrchr($Topic->attach_file, ".");
                            $file_ext = strtolower($file_ext);
                            ?>
                        <div class="bottom-article">
                            @if($file_ext ==".jpg"|| $file_ext ==".jpeg"|| $file_ext ==".png"|| $file_ext ==".gif")
                                <div class="text-center">
                                    <img src="{{ route("fileView",["path" =>'topics/'.$Topic->attach_file ]) }}"
                                         alt="{{ $title }}"/>
                                </div>
                            @else
                                <a href="{{ route("fileView",["path" =>'topics/'.$Topic->attach_file ]) }}">
                                    <strong><i class="fa fa-paperclip"></i>
                                        &nbsp;{{ __('frontend.downloadAttach') }}</strong>
                                </a>
                            @endif
                        </div>
                    @endif

                    @include(theme_view("topic.files"))
                </article>

            @endif

            <div class="velocity-contact-layout" data-velocity-reveal>
                <aside class="velocity-contact-details">
                    <div class="velocity-contact-intro">
                        <p class="velocity-eyebrow">{{ __('frontend.contactDetails') }}</p>
                        <h2>{{ $velocityTitle }}</h2>
                    </div>

                    @if(filled($Topic->{$velocityDetailsField}))
                        <div class="velocity-rich-content">{!! str_replace('"#', '"'.Request::url().'#', $Topic->{$velocityDetailsField}) !!}</div>
                    @endif

                    <div class="velocity-contact-list">
                        @if(Helper::GeneralSiteSettings('contact_t6'))
                            <div class="velocity-contact-detail">
                                <span class="velocity-contact-detail__icon" aria-hidden="true"><i class="fa-regular fa-envelope"></i></span>
                                <span class="velocity-contact-detail__content">
                                    <strong>{{ __('frontend.email') }}</strong>
                                    <a href="mailto:{{ Helper::GeneralSiteSettings('contact_t6') }}">{{ Helper::GeneralSiteSettings('contact_t6') }}</a>
                                </span>
                            </div>
                        @endif

                        @if(Helper::GeneralSiteSettings('contact_t3') || Helper::GeneralSiteSettings('contact_t5'))
                            <div class="velocity-contact-detail">
                                <span class="velocity-contact-detail__icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></span>
                                <span class="velocity-contact-detail__content">
                                    <strong>{{ __('frontend.callPhone') }}</strong>
                                    @foreach(['contact_t3', 'contact_t5'] as $velocityPhoneSetting)
                                        @if(Helper::GeneralSiteSettings($velocityPhoneSetting))
                                            <a class="velocity-ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', Helper::GeneralSiteSettings($velocityPhoneSetting)) }}">{{ Helper::GeneralSiteSettings($velocityPhoneSetting) }}</a>
                                        @endif
                                    @endforeach
                                </span>
                            </div>
                        @endif

                        @if(Helper::GeneralSiteSettings('contact_t1_'.$velocityLanguageCode))
                            <div class="velocity-contact-detail">
                                <span class="velocity-contact-detail__icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                                <span class="velocity-contact-detail__content">
                                    <strong>{{ __('frontend.address') }}</strong>
                                    <span>{{ Helper::GeneralSiteSettings('contact_t1_'.$velocityLanguageCode) }}</span>
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="velocity-contact-social">
                        <strong>{{ __('frontend.followUs') }}</strong>
                        @include(theme_view('layouts.social'), ['tt_position' => 'top'])
                    </div>
                </aside>

                @include(theme_view('components.contact-form'), [
                    'formId' => 'velocity-contact-form',
                ])
            </div>

            @include(theme_view('topic.maps'))
        </div>
    </section>

    @include(theme_view('layouts.popup'), ['Popup' => $Popup ?? null])
@endsection

@if($velocityCustomCss !== '')
    @push('after-styles')
        <style data-velocity-topic-css>{!! $velocityCustomCss !!}</style>
    @endpush
@endif
@if($velocityCustomJs !== '')
    @push('after-scripts')
        {!! $velocityCustomJs !!}
    @endpush
@endif
@if($velocityCustomBody !== '')
    @push('before-footer')
        {!! Blade::render($velocityCustomBody) !!}
    @endpush
@endif

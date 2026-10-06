@extends(theme_view('layouts.master'))

@section('content')
    <?php
    $custom_css_code = @$WebmasterSection->css_code;
    $custom_js_code = @$WebmasterSection->js_code;
    $custom_body_code = @$WebmasterSection->body_code;

    if (@$WebmasterSection != "none") {
        if (@$WebmasterSection->$title_var != "") {
            $webmaster_section_title = @$WebmasterSection->$title_var;
        } else {
            $webmaster_section_title = @$WebmasterSection->$title_var2;
        }
        $page_title = $webmaster_section_title;
        if (@$WebmasterSection->photo != "") {
            $category_image = route("fileView", ["path" => 'topics/'.@$WebmasterSection->photo]);
        }
    }

    $custom_css_code .= $Topic->css_code;
    $custom_js_code .= $Topic->js_code;
    $custom_body_code .= $Topic->body_code;
    ?>
    <div class="landing-page">

        @if($Topic->topicBlocks->where("status",1)->count() >0)
            @foreach($Topic->topicBlocks->where("status",1) as $TopicBlock)
                @if ($TopicBlock->content != "")
                        <?php
                        try {
                            $TopicBlockContents = json_decode($TopicBlock->content);
                        } catch (Exception $e) {
                            $TopicBlockContents = [];
                        }

                        // Stable hooks let every homepage block keep its own visual identity
                        // while preserving any custom classes configured in the dashboard.
                        $blockViewStyle = strtolower(str_replace(' ', '', @$TopicBlockContents->view_style));
                        $blockViewClass = $blockViewStyle !== '' ? ' topic-block--view-'.$blockViewStyle : '';
                        $blockModuleClass = ((int) @$TopicBlockContents->module_id > 0)
                            ? ' topic-block--module-'.(int) @$TopicBlockContents->module_id
                            : '';
                        ?>
                    <div class="topic-block topic-block--{{ (int) $TopicBlock->id }} topic-block--type-{{ (int) $TopicBlock->type }}{{ $blockViewClass }}{{ $blockModuleClass }}"
                         data-topic-block="{{ (int) $TopicBlock->id }}"
                         @if($blockViewStyle !== '') data-topic-view="{{ $blockViewStyle }}" @endif>
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
                            @include(theme_view('landing.').($blockViewStyle === 'yhinfographics' ? 'yhvisuals' : $blockViewStyle))
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
                    </div>
                @endif
            @endforeach
        @endif

        @include(theme_view('layouts.popup'),['Popup'=>@$Popup])
    </div>
@endsection
@if($custom_css_code !="" || $custom_js_code !="")
    @push('after-styles')
        @if($custom_css_code !="")
            <style>
                {!! $custom_css_code !!}
            </style>
        @endif
        {!! $custom_js_code !!}
    @endpush
@endif
@if($custom_body_code !="")
    @push('before-footer')
        {!! Blade::render($custom_body_code) !!}
    @endpush
@endif
@push('after-scripts')
    <script>
        (function () {
            var targets = document.querySelectorAll('.landing-page .meu-reveal, .landing-page .meu-stagger');
            if (!targets.length) return;
            if (!('IntersectionObserver' in window)) {
                targets.forEach(function (element) { element.classList.add('meu-in'); });
                return;
            }
            var observer = new IntersectionObserver(function (entries, instance) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('meu-in');
                        instance.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
            targets.forEach(function (element) { observer.observe(element); });
        })();
    </script>
@endpush

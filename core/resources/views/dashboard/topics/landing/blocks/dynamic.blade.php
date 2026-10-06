<input type="hidden" name="topic_id" class="form-control" value="{{ encrypt($Topic->id) }}">
<input type="hidden" name="block_id" class="form-control" value="{{ @$TopicBlock->id }}">
<input type="hidden" name="content_type" class="form-control" value="3">
<div class="light">
    <div class="row">
        @include("dashboard.topics.landing.blocks.settings")
        <div class="col-sm-9">
            <div class="p-a-2 white b-l">
                <div class="form-group">
                    @include("dashboard.topics.landing.blocks.meta")
                    <div class="form-group row">
                        <label for="view-styles"
                               class="col-sm-12 form-control-label m-b-sm">{!!  __('backend.viewStyle') !!}
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-12">
                            <div class="img-radio-group" id="view-styles">
                                @php
                                    use Illuminate\Support\Facades\File;

                                    $imagePath = public_path('assets/dashboard/images/blocks/');
                                    if (!File::exists($imagePath)) {
                                    $imagePath = str_replace('/core/public','',public_path('assets/dashboard/images/blocks/'));
                                    }
                                    $images = [];

                                    if (File::exists($imagePath)) {
                                        $images = File::files($imagePath);
                                        $images = array_filter($images, function($file) {
                                            return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        });
                                    }
                                    $yhStyleCards = [
                                        'YhScholars' => 'block_style_scholars',
                                        'YhNewsroom' => 'block_style_newsroom',
                                        'YhOpinions' => 'block_style_opinions',
                                        'YhAuthorArticles' => 'block_style_author_articles',
                                        'YhInfographics' => 'block_style_infographics',
                                        'YhVisuals' => 'block_style_visuals',
                                        'YhBooks' => 'block_style_books',
                                        'YhMagazine' => 'block_style_magazine',
                                        'YhFeaturedNews' => 'featured_news_slider',
                                    ];
                                    $selectedViewStyle = @$TopicBlockContents->view_style ?: (empty($TopicBlock) ? 'YhScholars' : '');
                                @endphp
                                @foreach($yhStyleCards as $style => $translationKey)
                                    <input type="radio" name="view_style" id="{{ $style }}" class="radio-input" value="{{ $style }}" {{ $selectedViewStyle === $style ? 'checked' : '' }} required>
                                    <label for="{{ $style }}" class="radio-label yh-block-style-card">
                                        <img src="{{ asset('assets/dashboard/images/blocks/'.$style.'.svg') }}" alt="" loading="lazy">
                                        <span class="text">{{ __('yemen_home.'.$translationKey) }}</span>
                                    </label>
                                @endforeach
                                @if(count($images) > 2)
                                    @php($i=0)
                                    @foreach($images as $image)
                                        @if(!in_array(pathinfo($image, PATHINFO_FILENAME),['banners','slider','Form']))
                                            <input type="radio" name="view_style" id="{{ pathinfo($image, PATHINFO_FILENAME) }}" class="radio-input" value="{{ pathinfo($image, PATHINFO_FILENAME) }}" {{ ($selectedViewStyle == pathinfo($image, PATHINFO_FILENAME))?"checked":"" }} required>
                                            <label for="{{ pathinfo($image, PATHINFO_FILENAME) }}" class="radio-label">
                                                <img src="{{ asset('assets/dashboard/images/blocks/' . $image->getFilename()) }}" alt="{{pathinfo($image, PATHINFO_FILENAME) }}">
                                                <div class="text">{{ pathinfo($image, PATHINFO_FILENAME) }}</div>
                                            </label>
                                            @php($i++)
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="module_id"
                               class="col-sm-12 form-control-label">{!!  __('backend.blockModule') !!}
                            <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-12">
                            <?php
                            $WebmasterSections = \App\Models\WebmasterSection::whereNotIn("type",
                                [4, 7, 6])->where("status", 1)->orderBy("row_no", "asc")->get();
                            ?>
                            <div>
                                <select name="module_id" id="module_id" class="form-control form-select2" required>
                                    <option value=""> - - {{ __("backend.select") }} - -</option>
                                    <?php
                                    $title_var = "title_".@Helper::currentLanguage()->code;
                                    $title_var2 = "title_".config('smartend.default_language');
                                    ?>
                                    @foreach($WebmasterSections as $WebmasterSection)
                                            <?php
                                            if ($WebmasterSection->$title_var != "") {
                                                $title = $WebmasterSection->$title_var;
                                            } else {
                                                $title = $WebmasterSection->$title_var2;
                                            }
                                            ?>
                                        <option value="{{$WebmasterSection->id}}" {{ (@$TopicBlockContents->module_id == $WebmasterSection->id)?"selected":"" }}>{{ $title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-12 form-control-label" for="category_ids">{!!  __('backend.blockModuleCategory') !!}</label>
                        <div class="col-sm-12">
                            <div id="module-categories">
                                <select name="category_ids[]" id="category_ids" class="form-control form-select2-multiple" multiple>
                                    <option value=""> - - {{ __("backend.select") }} - -</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group row">
                                <label for="records_count"
                                       class="col-sm-12 form-control-label">{!!  __('backend.blockModuleRecords') !!}
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-12">
                                    <div>
                                        <input type="number" name="records_count" id="records_count" value="{{ (@$TopicBlockContents->records_count >0)?@$TopicBlockContents->records_count:12 }}" min="1" max="200" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group row">
                                <label for="records_order"
                                       class="col-sm-12 form-control-label">{!!  __('backend.blockModuleOrder') !!}
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="col-sm-12">
                                    <div>
                                        <select name="records_order" id="records_order" class="form-control form-select2" required>
                                            <option value=""> - - {{ __("backend.select") }} - -</option>
                                            <option value="1" {{ (@$TopicBlockContents->records_order == 1 || empty(@$TopicBlockContents))?"selected":"" }}>{{ __("backend.blockModuleOrder1") }}</option>
                                            <option value="2" {{ (@$TopicBlockContents->records_order == 2)?"selected":"" }}>{{ __("backend.blockModuleOrder2") }}</option>
                                            <option value="3" {{ (@$TopicBlockContents->records_order == 3)?"selected":"" }}>{{ __("backend.blockModuleOrder3") }}</option>
                                            <option value="4" {{ (@$TopicBlockContents->records_order == 4)?"selected":"" }}>{{ __("backend.blockModuleOrder4") }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="yh-slider-settings" class="form-group m-t-2" style="display:none">
                        <div class="b-a p-a-2">
                            <h6 class="m-b-2"><i class="material-icons m-r-xs">view_column</i>{{ __('backend.sliderSettings') }}</h6>
                            <div class="row">
                                <div class="col-sm-4">
                                    <label for="slider_columns" class="form-control-label">{{ __('backend.sliderColumns') }}</label>
                                    <select name="slider_columns" id="slider_columns" class="form-control">
                                        @foreach([2, 3, 4, 5] as $columns)
                                            <option value="{{ $columns }}" {{ ((int) (@$TopicBlockContents->slider_columns ?: 3) === $columns) ? 'selected' : '' }}>{{ $columns }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label for="slider_speed" class="form-control-label">{{ __('backend.sliderSpeed') }}</label>
                                    <input type="number" name="slider_speed" id="slider_speed" class="form-control" min="1500" max="12000" step="500" value="{{ (int) (@$TopicBlockContents->slider_speed ?: 5000) }}">
                                    <small class="text-muted">{{ __('backend.sliderSpeedHint') }}</small>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-control-label d-block">{{ __('backend.sliderAutoplay') }}</label>
                                    <label class="ui-switch m-t-sm">
                                        <input type="checkbox" name="slider_autoplay" value="1" {{ (!isset($TopicBlockContents->slider_autoplay) || @$TopicBlockContents->slider_autoplay) ? 'checked' : '' }}>
                                        <i></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    #view-styles .yh-block-style-card {
        justify-content: flex-start;
        padding: 10px;
        background: #faf9f5;
        border: 2px solid #e7e8e2;
        border-radius: 12px;
        overflow: hidden;
    }
    #view-styles .yh-block-style-card img {
        display: block;
        width: 174px;
        height: 128px;
        object-fit: contain;
        opacity: 1;
        border-radius: 7px;
    }
    #view-styles .yh-block-style-card .text {
        width: 100%;
        padding-top: 11px;
        color: #223e36;
        text-align: center;
        font-weight: 700;
        line-height: 1.4;
    }
    #view-styles .radio-input:checked + .yh-block-style-card {
        background: #e8f1ec;
        border-color: #275b49;
        color: #223e36;
        box-shadow: 0 3px 12px rgba(20, 57, 42, .08);
        transform: none;
    }
    #view-styles .radio-input:checked + .yh-block-style-card img {
        filter: none;
        opacity: 1;
    }
    #view-styles .radio-input:focus-visible + .yh-block-style-card {
        outline: 3px solid #b08b39;
        outline-offset: 2px;
    }
</style>
<link rel="stylesheet" href="{{ URL::asset('assets/dashboard/js/select2-bootstrap-theme/dist/select2-bootstrap.css') }}?v={{ Helper::system_version() }}"/>
<link rel="stylesheet" href="{{ URL::asset('assets/dashboard/js/select2-bootstrap-theme/dist/select2-bootstrap.4.css') }}?v={{ Helper::system_version() }}"/>
<script src="{{ URL::asset('assets/dashboard/js/select2/dist/js/select2.min.js') }}?v={{ Helper::system_version() }}"></script>
<script>
    $(".form-select2").select2({
        theme: 'bootstrap',
        placeholder: "{{ __("backend.select") }}"
    });
    $(".form-select2-multiple").select2({
        tags: true,
        theme: 'bootstrap',
        placeholder: "{{ __("backend.select") }}"
    });
    $('#module_id').change(function () {
        var selectedValue = parseInt($(this).val());
        if (selectedValue > 0) {
            $('#module-categories').html("<div class=\"b-a p-x p-y-sm\"><img src=\"{{ asset('assets/dashboard/images/loading.gif') }}\" style=\"height: 22px;\"/> {{ __("backend.loading") }}..</div>");
            LoadModuleCats(selectedValue, '{{ @$TopicBlockContents->category_ids }}');
        }
    });

    function LoadModuleCats(id = 0, sel = '') {
        $.get("{{ route("getModuleCategories") }}/" + id + "/" + sel, function (data) {
            $('#module-categories').html(data);
            $(".form-select2-multiple").select2({
                tags: true,
                theme: 'bootstrap',
                placeholder: "{{ __("backend.select") }}"
            });
        });
    }

    @if(@$TopicBlockContents->module_id >0)
    LoadModuleCats(parseInt('{{ @$TopicBlockContents->module_id }}'), '{{ @$TopicBlockContents->category_ids }}');
    @endif

    var block_name = document.getElementById("block_name");
    var block_name_val = block_name.value;

    block_name.addEventListener('input', function () {
        block_name_val = block_name.value;
    });
    $('input[name="view_style"]').change(function () {
        if ($(this).is(':checked')) {
            var labelText = $('label[for="' + this.id + '"]').text();
            if (block_name_val === "") {
                document.getElementById("block_name").value = labelText.replace(/^\s+/, '').replace(/\s+$/, '').replace(/\n\s*\n/g, '\n').replace(/[ \t]+/g, ' ');
            }
            toggleYhSliderSettings();
        }
    });
    function toggleYhSliderSettings() {
        var selectedStyle = ($('input[name="view_style"]:checked').val() || '').toLowerCase();
        $('#yh-slider-settings').toggle(selectedStyle === 'yhopinions' || selectedStyle === 'yhfeaturednews');
    }
    toggleYhSliderSettings();
    moreBtnStatusSettingsView(1);
</script>

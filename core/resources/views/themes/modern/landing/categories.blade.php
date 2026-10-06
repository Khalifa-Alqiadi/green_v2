<?php
$title_var = "title_".@Helper::currentLanguage()->code;
$title_var2 = "title_".config('smartend.default_language');
$details_var = "details_".@Helper::currentLanguage()->code;
$details_var2 = "details_".config('smartend.default_language');

$block_style = "";
if (@$TopicBlock->bg_color != "") {
    $block_style = "background-color: ".@$TopicBlock->bg_color.";";
}
$block_classes = trim((string) @$TopicBlock->css_classes . (@$TopicBlock->divider_status ? ' divider' : ''));
if (@$TopicBlock->image_status && @$TopicBlockContents->{"bg_".@Helper::currentLanguage()->code} != "") {
    $block_style .= "background-image: url(".route("fileView",
            ["path" => 'topics/'.@$TopicBlockContents->{"bg_".@Helper::currentLanguage()->code}]).");background-size:cover;background-repeat: no-repeat;background-position: center top;";
}
$BlockCategories = [];
$section_url = "";
if (@$TopicBlockContents->module_id) {
    $BlockCategories = Helper::SectionCategories(@$TopicBlockContents->module_id, @$TopicBlockContents->records_count,
        @$TopicBlockContents->category_ids);
    $section_url = Helper::sectionURL(@$TopicBlockContents->module_id);
}
?>

@if(count($BlockCategories) >0)
    <section id="landing-block-{{ @$TopicBlock->id }}" class="categories landing-block yh-categories {{ $block_classes }}" style="{{ $block_style }}">
        <div class="container">
            @if(@$TopicBlock->title_status || @$TopicBlock->desc_status || @$TopicBlock->more_btn_status)
                <header class="yh-categories__head meu-reveal">
                    <div class="yh-categories__heading">
                        @if(@$TopicBlock->title_status)
                            <h2>{{ @$TopicBlockContents->{"title_".@Helper::currentLanguage()->code} ?: @$TopicBlockContents->{"title_".config('smartend.default_language')} }}</h2>
                        @endif
                        @if(@$TopicBlock->desc_status)
                            <p>{!! nl2br(e(@$TopicBlockContents->{"desc_".@Helper::currentLanguage()->code} ?: @$TopicBlockContents->{"desc_".config('smartend.default_language')})) !!}</p>
                        @endif
                    </div>
                    @if(@$TopicBlock->more_btn_status)
                        <a class="yh-categories__all" href="{{ url($section_url) }}">
                            {{ __('frontend.viewMore') }}
                            <i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long" aria-hidden="true"></i>
                        </a>
                    @endif
                </header>
            @endif
            <div class="yh-categories__grid meu-stagger">
                @foreach($BlockCategories as $Category)
                        <?php
                        if ($Category->$title_var != "") {
                            $title = $Category->$title_var;
                        } else {
                            $title = $Category->$title_var2;
                        }
                        $details = $Category->$details_var ?: $Category->$details_var2;
                        $category_link_url = Helper::categoryURL($Category->id, "", $Category);
                        $HomeSectionType = @$Topic->webmasterSection->type;
                        if (!@$require_mp3_player && $HomeSectionType == 3) {
                            $require_mp3_player = 1;
                        }
                        ?>
                    <a class="yh-category-card" href="{{ $category_link_url }}" aria-label="{{ $title }}">
                        <span class="yh-category-card__media" aria-hidden="true">
                            @if($Category->photo != '')
                                <img src="{{ route('fileView', ['path' => 'sections/'.$Category->photo]) }}?w=180&h=180&r=fit"
                                     alt="" loading="lazy" decoding="async">
                            @elseif($Category->icon != '')
                                <i class="{{ $Category->icon }}"></i>
                            @else
                                <i class="bi bi-book-half"></i>
                            @endif
                        </span>
                        <span class="yh-category-card__copy">
                            <strong>{{ $title }}</strong>
                            @if(trim(strip_tags((string) $details)) !== '')
                                <span>{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $details)), 140) }}</span>
                            @endif
                        </span>
                        <span class="yh-category-card__arrow" aria-hidden="true"><i class="fa-solid fa-arrow-{{ @Helper::currentLanguage()->right }}-long"></i></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

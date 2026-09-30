@if(!empty($Topic))
    <?php
    $works_media_url = "";
    $works_kicker = "";
    $works_section_type = @$Topic->webmasterSection->type;

    if (@$Topic->photo_file != "") {
        $works_media_url = route("fileView", ["path" => 'topics/'.@$Topic->photo_file])."?w=980&h=520";
    } elseif ($works_section_type == 2 && @$Topic->video_type == 1 && @$Topic->video_file != "") {
        $Youtube_id = Helper::Get_youtube_video_id($Topic->video_file);
        if ($Youtube_id != "") {
            $works_media_url = "https://img.youtube.com/vi/".$Youtube_id."/0.jpg";
        }
    } elseif (@$Topic->photos && count($Topic->photos) > 0) {
        $works_media_url = route("fileView", ["path" => 'topics/'.@$Topic->photos->first()->file])."?w=980&h=520";
    } else {
        foreach (@$Topic->webmasterSection->customFields->where("type", 8) as $TopicPhotoCustomField) {
            if ($TopicPhotoCustomField->lang_code == "all" || $TopicPhotoCustomField->lang_code == @Helper::currentLanguage()->code) {
                foreach (@$Topic->fields->where("field_id", $TopicPhotoCustomField->id) as $Photo) {
                    if (@$Photo->field_value != "") {
                        $works_media_url = route("fileView", ["path" => 'topics/'.@$Photo->field_value])."?w=980&h=520";
                        break 2;
                    }
                }
            }
        }
    }

    $works_category = @$Topic->category(@$Topic->id);
    if (@$works_category->$title_var != "") {
        $works_kicker = @$works_category->$title_var;
    } elseif (@$works_category->$title_var2 != "") {
        $works_kicker = @$works_category->$title_var2;
    } elseif (@$Topic->webmasterSection->$title_var != "") {
        $works_kicker = @$Topic->webmasterSection->$title_var;
    } elseif (@$Topic->webmasterSection->$title_var2 != "") {
        $works_kicker = @$Topic->webmasterSection->$title_var2;
    }
    ?>
    <article class="modern-works-card">
        <a href="{{ $topic_link_url }}" class="modern-works-card-link" aria-label="{{ $title }}">
            <div class="modern-works-media">
                @if($works_media_url != "")
                    <img src="{{ $works_media_url }}" alt="{{ Helper::topicPhotoAlt($Topic) }}" loading="lazy"/>
                @else
                    <div class="modern-works-media-placeholder">
                        <i class="fa-regular fa-image"></i>
                    </div>
                @endif
            </div>
            <div class="modern-works-content">
                @if($works_kicker != "")
                    <span class="modern-works-kicker">{{ $works_kicker }}</span>
                @endif
                <div class="modern-works-title-row">
                    <h3 class="modern-works-title">{{ $title }}</h3>
                    <span class="modern-works-arrow" aria-hidden="true">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </div>
        </a>
        @if(Helper::GeneralWebmasterSettings('authors_status') && $Topic->authors->where('status', true)->isNotEmpty())
            <div class="modern-works-card__authors">
                @include(theme_view('components.author-links'), ['authors' => $Topic->authors->where('status', true)])
            </div>
        @endif
    </article>
@endif

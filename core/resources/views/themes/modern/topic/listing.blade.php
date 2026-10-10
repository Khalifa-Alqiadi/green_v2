<?php
$title_var = "title_".@Helper::currentLanguage()->code;
$title_var2 = "title_".config('smartend.default_language');
$details_var = "details_".@Helper::currentLanguage()->code;
$details_var2 = "details_".config('smartend.default_language');
$slug_var = "seo_url_slug_".@Helper::currentLanguage()->code;
$slug_var2 = "seo_url_slug_".config('smartend.default_language');
$isNewsSection = (int) ($TopicsList->first()->webmaster_id ?? 0) === 3;
$isScholarSection = (int) ($TopicsList->first()->webmaster_id ?? 0) === 15;
$isMagazineSection = (int) ($TopicsList->first()->webmaster_id ?? 0) === 18;
$isVideoSection = (int) data_get($TopicsList->first(), 'webmasterSection.type') === 2;

$cols_lg = 4;
$cols_md = 6;
if (count($Categories) > 0) {
    $cols_lg = 6;
    $cols_md = 12;
}
if ($isScholarSection) {
    $cols_lg = 6;
    $cols_md = 6;
}
?>
@if(@$TopicsList->count() >0)
    <div class="{{ $isNewsSection ? 'yh-news-list' : ($isMagazineSection ? 'yh-issue-grid' : ($isScholarSection ? 'yh-scholar-grid' : ($isVideoSection ? 'yh-video-grid' : 'row'))) }}">
        @foreach($TopicsList as $Topic)
                <?php
                if ($Topic->$title_var != "") {
                    $title = $Topic->$title_var;
                } else {
                    $title = $Topic->$title_var2;
                }
                if ($Topic->$details_var != "") {
                    $details = $details_var;
                } else {
                    $details = $details_var2;
                }

                $topic_link_url = Helper::topicURL($Topic->id, "", $Topic);
                ?>
            @if($isNewsSection)
                @include(theme_view('topic.news-list-card'), ['Topic' => $Topic])
            @elseif($isMagazineSection)
                @include(theme_view('topic.magazine-card'), ['Topic' => $Topic])
            @elseif($isScholarSection)
                @include(theme_view('topic.scholar-card'), ['Topic' => $Topic])
            @elseif($isVideoSection)
                @include(theme_view('topic.video-card'), ['Topic' => $Topic])
            @else
                <div class="col-lg-{{$cols_lg}} col-md-{{$cols_md}}">
                    @include(theme_view("topic.card"),["Topic"=>$Topic])
                </div>
            @endif
        @endforeach
    </div>
@endif

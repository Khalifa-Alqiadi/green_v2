<?php
$title_var = "title_".@Helper::currentLanguage()->code;
$title_var2 = "title_".config('smartend.default_language');
$details_var = "details_".@Helper::currentLanguage()->code;
$details_var2 = "details_".config('smartend.default_language');
$slug_var = "seo_url_slug_".@Helper::currentLanguage()->code;
$slug_var2 = "seo_url_slug_".config('smartend.default_language');
$isNewsSection = (int) ($TopicsList->first()->webmaster_id ?? 0) === 3;

$cols_lg = 4;
$cols_md = 6;
if (count($Categories) > 0) {
    $cols_lg = 6;
    $cols_md = 12;
}
?>
@if(@$TopicsList->count() >0)
    <div class="{{ $isNewsSection ? 'yh-news-list' : 'row' }}">
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
            @else
                <div class="col-lg-{{$cols_lg}} col-md-{{$cols_md}}">
                    @include(theme_view("topic.card"),["Topic"=>$Topic])
                </div>
            @endif
        @endforeach
    </div>
@endif

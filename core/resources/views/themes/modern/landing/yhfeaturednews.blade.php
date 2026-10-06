@php
    extract(require resource_path('views/themes/modern/landing/_yh-data.php'));

    $featuredModuleId = (int) data_get($TopicBlockContents, 'module_id', 0);
    $featuredLimit = min(12, max(1, (int) data_get($TopicBlockContents, 'records_count', 5)));
    $featuredCategoryIds = collect(explode(',', (string) data_get($TopicBlockContents, 'category_ids', '')))
        ->map(fn ($id) => (int) trim($id))
        ->filter(fn ($id) => $id > 0)
        ->all();

    $featuredNews = collect();
    if ($featuredModuleId > 0) {
        $featuredNews = \App\Models\Topic::query()
            ->where('webmaster_id', $featuredModuleId)
            ->where('status', 1)
            ->where('featured', 1)
            ->where(function ($query) {
                $query->whereNull('expire_date')->orWhereDate('expire_date', '>=', today());
            })
            ->when($featuredCategoryIds, function ($query) use ($featuredCategoryIds) {
                $query->whereHas('categories', fn ($category) => $category->whereIn('section_id', $featuredCategoryIds));
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($featuredLimit)
            ->get();
    }

    $featuredSliderSpeed = min(12000, max(1500, (int) data_get($TopicBlockContents, 'slider_speed', 5000)));
    $featuredSliderAutoplay = (int) data_get($TopicBlockContents, 'slider_autoplay', 1) === 1;
@endphp

@if($featuredNews->isNotEmpty())
    @include(theme_view('layouts.featured-news-slider'))
@endif

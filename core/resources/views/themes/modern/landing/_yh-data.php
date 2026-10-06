<?php

$yhLanguage = optional(Helper::currentLanguage())->code ?: config('smartend.default_language');
$yhFallbackLanguage = config('smartend.default_language');
$yhTitleField = 'title_'.$yhLanguage;
$yhFallbackTitleField = 'title_'.$yhFallbackLanguage;
$yhDetailsField = 'details_'.$yhLanguage;
$yhFallbackDetailsField = 'details_'.$yhFallbackLanguage;
$yhBlockTitle = data_get($TopicBlockContents, 'title_'.$yhLanguage)
    ?: data_get($TopicBlockContents, 'title_'.$yhFallbackLanguage);
$yhBlockDescription = data_get($TopicBlockContents, 'desc_'.$yhLanguage)
    ?: data_get($TopicBlockContents, 'desc_'.$yhFallbackLanguage);
$yhTopics = collect();
$yhSectionUrl = '#';

if ((int) data_get($TopicBlockContents, 'module_id') > 0) {
    $yhTopics = collect(Helper::Topics(
        (int) data_get($TopicBlockContents, 'module_id'),
        data_get($TopicBlockContents, 'category_ids', ''),
        (int) data_get($TopicBlockContents, 'records_count', 8),
        0,
        (int) data_get($TopicBlockContents, 'records_order', 1)
    ));
    $yhSectionUrl = url(Helper::sectionURL((int) data_get($TopicBlockContents, 'module_id')));
}

$yhTopicTitle = static fn ($item) => $item->{$yhTitleField} ?: $item->{$yhFallbackTitleField};
$yhTopicDetails = static fn ($item) => $item->{$yhDetailsField} ?: $item->{$yhFallbackDetailsField};

return compact(
    'yhLanguage', 'yhFallbackLanguage', 'yhTitleField', 'yhFallbackTitleField',
    'yhDetailsField', 'yhFallbackDetailsField', 'yhBlockTitle', 'yhBlockDescription',
    'yhTopics', 'yhSectionUrl', 'yhTopicTitle', 'yhTopicDetails'
);

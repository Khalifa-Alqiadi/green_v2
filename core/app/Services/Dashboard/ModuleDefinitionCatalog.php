<?php

namespace App\Services\Dashboard;

class ModuleDefinitionCatalog
{
    public const MODULE_TYPES = [10, 1, 2, 3, 5, 8, 9, 4, 7, 6];

    public const MODULE_TYPE_LABELS = [
        10 => 'Landing Pages',
        1 => 'Photo Gallery',
        2 => 'Video Gallery',
        3 => 'Audio Library',
        5 => 'Data Tables',
        8 => 'Accordion View (FAQ)',
        9 => 'Documentation Section',
        4 => 'Private Admin Data',
        7 => 'Private Searchable Data',
        6 => 'Public Form Builder',
    ];

    public const MODULE_TYPE_DESCRIPTIONS = [
        10 => 'Flexible marketing and service landing pages built from dynamic content blocks.',
        1 => 'Image albums, portfolios, events, and media collections with gallery previews.',
        2 => 'Uploaded videos or YouTube and Vimeo showcases, tutorials, and presentations.',
        3 => 'Uploaded MP3 podcasts, interviews, lessons, and audio archives.',
        5 => 'Structured listings, directories, reports, datasets, and sortable analytical records.',
        8 => 'Categorized and filterable frequently asked questions or other accordion content.',
        9 => 'Manuals, guides, knowledge bases, and searchable documentation with sidebar navigation.',
        4 => 'Records accessible only to authorized dashboard users.',
        7 => 'Privately managed structured records searchable from a public verification page.',
        6 => 'Public inquiry, request, registration, and submission forms.',
    ];

    public const BASIC_FIELDS = [
        'title_status' => 'Title',
        'date_status' => 'Date Field',
        'expire_date_status' => 'Expire Date Field',
        'longtext_status' => 'Long Text Field',
        'editor_status' => 'Allow Editor',
        'tags_status' => 'Tags Field',
        'authors_status' => 'Authors Field',
        'case_status' => 'Status Field',
        'featured_status' => 'Featured Field',
        'visits_status' => 'Visits Field',
        'photo_status' => 'Attach Photo Field',
        'attach_file_status' => 'Attach File Field',
        'section_icon_status' => 'Section Icon Picker',
        'icon_status' => 'Topics Icon Picker',
        'no_status' => 'Show ID Column',
        'index_status' => 'Allow Google Indexing',
    ];

    public const ADDITIONAL_TABS = [
        'multi_images_status' => 'Additional Images',
        'extra_attach_file_status' => 'Additional Files',
        'maps_status' => 'Attach Google Maps',
        'order_status' => 'Attach Order Form',
        'comments_status' => 'Comments Available',
        'related_status' => 'Related Topics',
        'seo_status' => 'SEO Settings',
        'code_status' => 'Custom Code',
    ];

    public const CUSTOM_FIELD_TYPES = [
        0 => 'Text Box', 1 => 'Text Area', 16 => 'Text Editor', 2 => 'Number',
        15 => 'Phone Number with Country Code', 3 => 'Email Address', 4 => 'Date',
        5 => 'Date & Time', 6 => 'Select', 13 => 'Select (Radio)', 7 => 'Multi Select',
        14 => 'Checkbox', 17 => 'Multi Checkboxes', 18 => 'Select Record from Another Module',
        8 => 'Photo File', 9 => 'Attach File', 10 => 'Video File',
        11 => 'YouTube Video Link', 12 => 'Vimeo Video Link', 99 => 'Fixed Text or Divider',
    ];

    public const CHOICE_FIELD_TYPES = [6, 7, 13, 17];

    public const CUSTOM_FIELD_DESCRIPTIONS = [
        0 => 'Short single-line reference code, company name, address, job title, or model identifier. Not numeric data, multiline content, or known finite choices.',
        1 => 'Plain multiline notes, summaries, requirements, instructions, or address details. No formatting. Do not combine unrelated structured properties here.',
        16 => 'Rich formatted biography, detailed specifications, or terms. Only genuinely unstructured content; prefer the equivalent basic description/editor. Generated defaults must still be plain text.',
        2 => 'Genuine numeric price, quantity, area, capacity, experience, or percentage. Prefer over Text Box for numbers. Existing defaults must be nonnegative numbers.',
        15 => 'Phone, mobile, WhatsApp, or contact number with country-code handling; not a general text value.',
        3 => 'Email address with email validation, rather than Text Box.',
        4 => 'Calendar date without time, such as birth or event date. Prefer basic Date or Expire Date for equivalent purposes. Default format Y-m-d.',
        5 => 'Calendar date and exact appointment, reservation, visit, or event time. Default format Y-m-d H:i or Y-m-d H:i:s.',
        6 => 'One choice from a medium or large fixed list in a compact dropdown: property type, condition, level, department, priority, or fixed city list.',
        13 => 'One choice from a small visible list, typically two to five short options: individual/company, online/onsite, new/used. Prefer Select for longer lists.',
        7 => 'Multiple choices from a fixed list in a compact control: services, languages, amenities, features, expertise, or audiences. Never comma-separated free text.',
        14 => 'One boolean yes/no property: available, remote support, warranty, or approval required. No options. Default may only be 0 or 1.',
        17 => 'Multiple choices from a small fixed list displayed as visible checkboxes. Prefer Multi Select when the list would make the form too long.',
        18 => 'Reference an existing record using parent_module_id from ELIGIBLE_RELATED_MODULES only. Prefer an existing Brands, Cities, Departments, Instructors, or Manufacturers module over duplicate text or choices. Never invent IDs. No options. Default null because records are not supplied.',
        8 => 'One additional image with a distinct purpose: logo, floor plan, identity photo, certificate. Prefer equivalent basic Attach Photo. No default.',
        9 => 'One specific brochure, PDF, resume, certificate, or download. Prefer equivalent basic Attach File; use Additional Files for unrestricted multiple attachments. No default.',
        10 => 'One specific uploaded video per record. No default. Distinct from externally hosted YouTube or Vimeo URLs.',
        11 => 'A dedicated YouTube video URL, not an uploaded video or Vimeo link.',
        12 => 'A dedicated Vimeo video URL, not an uploaded video or YouTube link.',
        99 => 'Static form section divider or brief plain-text instructions only. Does not collect record data. Provide localized fixed_texts; no default or options.',
    ];

    public const FILE_FIELD_TYPES = [8, 9, 10];

    public const MULTI_VALUE_FIELD_TYPES = [7, 17];

    public function forPrompt(): array
    {
        return [
            'module_types' => collect(self::MODULE_TYPE_LABELS)->mapWithKeys(fn ($label, $type) => [
                $type => ['label' => $label, 'description' => self::MODULE_TYPE_DESCRIPTIONS[$type]],
            ])->all(),
            'basic_fields' => self::BASIC_FIELDS,
            'additional_tabs' => self::ADDITIONAL_TABS,
            'custom_field_types' => collect(self::CUSTOM_FIELD_TYPES)->mapWithKeys(fn ($label, $type) => [
                $type => [
                    'label' => $label,
                    'purpose' => self::CUSTOM_FIELD_DESCRIPTIONS[$type],
                    'options' => in_array($type, self::CHOICE_FIELD_TYPES, true) ? 'Ordered objects: labels for every active language; color null or #RRGGBB. Maximum 50. Partial colors retain their position.' : 'Empty array only.',
                    'parent_module_id' => $type === 18 ? 'Required eligible module ID.' : 'Null only.',
                    'fixed_texts' => $type === 99 ? 'Plain text for every active language.' : 'Empty array only.',
                    'default' => in_array($type, [...self::FILE_FIELD_TYPES, ...self::MULTI_VALUE_FIELD_TYPES, 99], true)
                        ? 'Null only.' : (in_array($type, [6, 13], true) ? 'Null or a one-based option index encoded as a string.' : 'Null or a type-valid scalar string.'),
                ],
            ])->all(),
            'limits' => ['custom_fields' => 25, 'options_per_field' => 50],
        ];
    }
}

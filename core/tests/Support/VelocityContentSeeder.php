<?php

namespace Tests\Support;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VelocityContentSeeder extends Seeder
{
    public const PREFIX = 'velocity-test';

    public function run(): void
    {
        DB::transaction(function (): void {
            $moduleIds = [];

            foreach (range(0, 10) as $type) {
                $moduleIds[$type] = $this->upsertModule($type);
            }

            $topicIds = [];
            foreach ($moduleIds as $type => $moduleId) {
                $topicIds[$type] = $this->upsertTopic($moduleId, $type);
            }

            $this->upsertTopic($moduleIds[0], 0, 'empty', [
                'details_en' => null,
                'details_ar' => null,
                'photo_file' => null,
                'attach_file' => null,
                'featured' => 0,
            ]);

            $this->seedMedia($topicIds, $moduleIds);
            $this->seedCustomFields($moduleIds[6], $topicIds[6], $moduleIds[0], $topicIds[0]);
            $this->seedLandingBlocks($topicIds[10], $moduleIds[6], $moduleIds[0]);
            $this->seedPopup($moduleIds[6]);
        });
    }

    private function upsertModule(int $type): int
    {
        $slug = self::PREFIX.'-module-'.$type;
        $now = now();

        DB::table('webmaster_sections')->updateOrInsert(
            ['seo_url_slug_en' => $slug],
            [
                'row_no' => 900 + $type,
                'title_en' => 'Velocity Test Module '.$type,
                'title_ar' => 'وحدة اختبار فيلوسيتي '.$type,
                'seo_title_en' => 'Velocity Test Module '.$type,
                'seo_title_ar' => 'وحدة اختبار فيلوسيتي '.$type,
                'seo_url_slug_ar' => $slug.'-ar',
                'type' => $type,
                'title_status' => 1,
                'photo_status' => 1,
                'tags_status' => 1,
                'case_status' => 1,
                'featured_status' => 1,
                'index_status' => 1,
                'visits_status' => 1,
                'sections_status' => 1,
                'comments_status' => 1,
                'date_status' => 1,
                'expire_date_status' => 1,
                'longtext_status' => 1,
                'editor_status' => 1,
                'attach_file_status' => 1,
                'extra_attach_file_status' => 1,
                'multi_images_status' => 1,
                'section_icon_status' => 1,
                'icon_status' => 1,
                'maps_status' => 1,
                'order_status' => 1,
                'related_status' => 1,
                'seo_status' => 1,
                'code_status' => 1,
                'no_status' => 1,
                'status' => 1,
                'css_code' => $type === 10 ? '.velocity-test-section { outline: 1px solid transparent; }' : null,
                'js_code' => $type === 10 ? '<script>window.velocityTest = true;</script>' : null,
                'body_code' => $type === 10 ? '<div data-velocity-test="module"></div>' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) DB::table('webmaster_sections')->where('seo_url_slug_en', $slug)->value('id');
    }

    private function upsertTopic(int $moduleId, int $type, string $suffix = 'primary', array $overrides = []): int
    {
        $slug = self::PREFIX.'-topic-'.$type.'-'.$suffix;
        $now = now();
        $values = array_merge([
            'title_en' => 'Velocity Test Topic '.$type.' '.$suffix,
            'title_ar' => 'موضوع اختبار فيلوسيتي '.$type.' '.$suffix,
            'details_en' => '<p>Deterministic Velocity test content.</p>',
            'details_ar' => '<p>بيانات ثابتة لاختبار قالب فيلوسيتي.</p>',
            'date' => '2026-01-15',
            'expire_date' => null,
            'status' => 1,
            'featured' => 1,
            'visits' => 17,
            'webmaster_id' => $moduleId,
            'section_id' => 0,
            'row_no' => 900 + $type,
            'seo_title_en' => 'Velocity Test Topic '.$type,
            'seo_title_ar' => 'موضوع اختبار فيلوسيتي '.$type,
            'seo_description_en' => 'Deterministic Velocity test content.',
            'seo_description_ar' => 'بيانات ثابتة لاختبار قالب فيلوسيتي.',
            'seo_url_slug_ar' => $slug.'-ar',
            'css_code' => $type === 10 ? '.velocity-test-topic { display: block; }' : null,
            'js_code' => $type === 10 ? '<script>window.velocityTestTopic = true;</script>' : null,
            'body_code' => $type === 10 ? '<div data-velocity-test="topic"></div>' : null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);

        DB::table('topics')->updateOrInsert(['seo_url_slug_en' => $slug], $values);

        return (int) DB::table('topics')->where('seo_url_slug_en', $slug)->value('id');
    }

    private function seedMedia(array $topicIds, array $moduleIds): void
    {
        $now = now();

        DB::table('topics')->where('id', $topicIds[0])->update([
            'photo_file' => self::PREFIX.'-cover.jpg',
            'attach_file' => self::PREFIX.'-document.pdf',
            'updated_at' => $now,
        ]);

        DB::table('topics')->where('id', $topicIds[2])->update([
            'video_type' => 0,
            'video_file' => self::PREFIX.'-video.mp4',
            'updated_at' => $now,
        ]);
        $videoEmbedId = $this->upsertTopic($moduleIds[2], 2, 'embed', [
            'video_type' => 3,
            'video_file' => '<iframe title="Velocity test video" src="about:blank"></iframe>',
        ]);

        DB::table('topics')->where('id', $topicIds[3])->update([
            'video_type' => 0,
            'audio_file' => self::PREFIX.'-audio.mp3',
            'updated_at' => $now,
        ]);
        $audioEmbedId = $this->upsertTopic($moduleIds[3], 3, 'embed', [
            'video_type' => 3,
            'audio_file' => '<iframe title="Velocity test audio" src="about:blank"></iframe>',
        ]);

        DB::table('photos')->updateOrInsert(
            ['topic_id' => $topicIds[1], 'file' => self::PREFIX.'-gallery.jpg'],
            ['title' => 'Velocity test gallery image', 'row_no' => 1, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('attach_files')->updateOrInsert(
            ['topic_id' => $topicIds[0], 'file' => self::PREFIX.'-extra.pdf'],
            ['title_en' => 'Velocity test attachment', 'title_ar' => 'مرفق اختبار فيلوسيتي', 'row_no' => 1, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('maps')->updateOrInsert(
            ['topic_id' => $topicIds[0], 'title_en' => 'Velocity test map'],
            [
                'longitude' => '31.235712',
                'latitude' => '30.044420',
                'title_ar' => 'خريطة اختبار فيلوسيتي',
                'details_en' => 'Cairo test location',
                'details_ar' => 'موقع القاهرة للاختبار',
                'icon' => 0,
                'status' => 1,
                'row_no' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        DB::table('comments')->updateOrInsert(
            ['topic_id' => $topicIds[0], 'email' => 'velocity-test@example.test'],
            [
                'name' => 'Velocity Test',
                'date' => '2026-01-15 12:00:00',
                'comment' => 'Deterministic approved comment.',
                'status' => 1,
                'row_no' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('topics')->whereIn('id', [$videoEmbedId, $audioEmbedId])->update(['featured' => 0]);
    }

    private function seedCustomFields(
        int $formModuleId,
        int $formTopicId,
        int $parentModuleId,
        int $parentTopicId
    ): void {
        $now = now();
        $optionTypes = [6, 7, 13, 17];

        foreach (array_merge(range(0, 18), [99]) as $type) {
            $title = 'Velocity Test Field '.$type;

            DB::table('webmaster_section_fields')->updateOrInsert(
                ['webmaster_id' => $formModuleId, 'title_en' => $title],
                [
                    'type' => $type,
                    'title_ar' => 'حقل اختبار فيلوسيتي '.$type,
                    'default_value' => null,
                    'parent_module_id' => $type === 18 ? $parentModuleId : null,
                    'details_en' => in_array($type, $optionTypes, true) ? "First option\nSecond option\nThird option" : null,
                    'details_ar' => in_array($type, $optionTypes, true) ? "الخيار الأول\nالخيار الثاني\nالخيار الثالث" : null,
                    'row_no' => 900 + $type,
                    'status' => 1,
                    'required' => 0,
                    'in_table' => 1,
                    'in_search' => 1,
                    'in_listing' => 1,
                    'in_page' => 1,
                    'in_statics' => in_array($type, $optionTypes, true) ? 1 : 0,
                    'lang_code' => 'all',
                    'css_class' => 'velocity-test-field',
                    'colors' => in_array($type, $optionTypes, true) ? "#0f766e\n#0369a1\n#7c3aed" : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if ($type === 99) {
                continue;
            }

            $fieldId = (int) DB::table('webmaster_section_fields')
                ->where('webmaster_id', $formModuleId)
                ->where('title_en', $title)
                ->value('id');

            DB::table('topic_fields')->updateOrInsert(
                ['topic_id' => $formTopicId, 'field_id' => $fieldId],
                ['field_value' => $this->fieldValue($type, $parentTopicId), 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    private function seedLandingBlocks(int $landingTopicId, int $formModuleId, int $contentModuleId): void
    {
        $now = now();
        $bannerAreaId = (int) DB::table('webmaster_banners')->orderBy('id')->value('id');

        $blocks = [
            ['Static', 0, ['title_en' => 'Static content', 'details_en' => '<p>Velocity test static block.</p>']],
            ['Custom code', 1, ['code' => '<div data-velocity-test="code">Custom code</div>']],
            ['Banner items', 2, ['banner_area_id' => $bannerAreaId, 'banner_style' => 'banners']],
            ['Banner slider', 2, ['banner_area_id' => $bannerAreaId, 'banner_style' => 'slider']],
            ['Contact form', 4, ['module_id' => null, 'view_style' => 'Contact']],
            ['Public form', 4, ['module_id' => $formModuleId, 'view_style' => 'Form']],
        ];

        foreach (['Carousel', 'Categories', 'FAQ', 'Gallery', 'Grid', 'News', 'Partners', 'Staff', 'Testimonials'] as $style) {
            $blocks[] = [
                'Dynamic '.$style,
                3,
                [
                    'module_id' => $contentModuleId,
                    'category_ids' => null,
                    'records_count' => 6,
                    'records_order' => 1,
                    'view_style' => $style,
                ],
            ];
        }

        foreach ($blocks as $index => [$name, $type, $content]) {
            DB::table('topic_blocks')->updateOrInsert(
                ['topic_id' => $landingTopicId, 'block_name' => self::PREFIX.' '.$name],
                [
                    'row_no' => 900 + $index,
                    'type' => $type,
                    'content' => json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'title_status' => 1,
                    'desc_status' => 1,
                    'image_status' => 1,
                    'divider_status' => 1,
                    'more_btn_status' => 1,
                    'bg_color' => '#f8fafc',
                    'css_classes' => 'velocity-test-section',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedPopup(int $formModuleId): void
    {
        $now = now();

        DB::table('popups')->updateOrInsert(
            ['title_en' => 'Velocity Test Popup'],
            [
                'row_no' => 900,
                'form_id' => $formModuleId,
                'show_in' => 0,
                'title_ar' => 'نافذة اختبار فيلوسيتي',
                'details_en' => '<p>Velocity test popup.</p>',
                'details_ar' => '<p>نافذة اختبار لقالب فيلوسيتي.</p>',
                'photo' => self::PREFIX.'-popup.jpg',
                'settings' => json_encode(['delay' => 0], JSON_THROW_ON_ERROR),
                'code' => '<div data-velocity-test="popup"></div>',
                'status' => 1,
                'start_date' => null,
                'end_date' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function fieldValue(int $type, int $parentTopicId): string
    {
        return match ($type) {
            2 => '42',
            3 => 'velocity-test@example.test',
            4 => '2026-01-15',
            5 => '2026-01-15 12:30:00',
            6, 13 => '1',
            7, 17 => '1, 2',
            8 => self::PREFIX.'-field-file.pdf',
            9 => self::PREFIX.'-field-image.jpg',
            10 => self::PREFIX.'-field-video.mp4',
            11 => '31.235712',
            12 => '30.044420',
            14 => '1',
            15 => '201000000000',
            16 => 'https://example.test/velocity-test',
            18 => (string) $parentTopicId,
            default => 'Velocity test value',
        };
    }
}

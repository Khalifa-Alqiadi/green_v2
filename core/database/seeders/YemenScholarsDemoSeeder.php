<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Section;
use App\Models\Topic;
use App\Models\TopicBlock;
use App\Models\TopicCategory;
use App\Models\TopicField;
use App\Models\WebmasterSection;
use App\Models\WebmasterSectionField;
use App\Models\WebmasterSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class YemenScholarsDemoSeeder extends Seeder
{
    /**
     * Seed editable demo content for the Yemen Scholars media platform.
     *
     * Text is deliberately concise and carries a source link. Running this
     * seeder more than once updates its own records instead of duplicating them.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->syncDemoImages();
            $this->ensureContentStructure();
            $modules = $this->resolveModules();

            if (! isset($modules['news'], $modules['articles'], $modules['scholars'])) {
                $this->command?->warn('Yemen Scholars demo seeder: required modules were not found; no data changed.');

                return;
            }

            $authors = $this->seedAuthors();
            $this->seedTopics($modules, $authors);
            $this->seedLandingBlocks($modules);
        });
    }

    /**
     * Create only the modules and categories required by this demo package.
     * Existing records are reused, so rerunning the seeder stays idempotent.
     */
    private function ensureContentStructure(): void
    {
        $definitions = [
            'news' => ['الأخبار', 'News', 'news', 0, true, false],
            'articles' => ['المقالات', 'Articles', 'articles', 0, true, true],
            'photos' => ['الصور', 'Photos', 'photos', 1, false, false],
            'scholars' => ['دليل العلماء', 'Scholars Directory', 'scholars-directory', 0, false, false],
            'infographics' => ['إنفوجرافيك', 'Infographic', 'infographic', 1, false, false],
            'publications' => ['إصدارات', 'Publications', 'publications', 0, false, true],
            'magazine' => ['مجلة المنبر اليمني', 'Al Minbar Al Yemeni Magazine', 'al-minbar-magazine', 0, false, false],
        ];

        $rowNo = ((int) WebmasterSection::query()->max('row_no')) + 1;

        foreach ($definitions as $key => [$titleAr, $titleEn, $slug, $type, $hasSections, $hasAuthors]) {
            $module = WebmasterSection::query()
                ->where('title_ar', $titleAr)
                ->orWhere('title_en', $titleEn)
                ->first();

            if (! $module) {
                $module = new WebmasterSection;
                $module->row_no = $rowNo++;
                $module->title_ar = $titleAr;
                $module->title_en = $titleEn;
                $module->type = $type;
                $module->title_status = 1;
                $module->photo_status = 1;
                $module->tags_status = 1;
                $module->case_status = 1;
                $module->featured_status = 1;
                $module->index_status = 1;
                $module->visits_status = 1;
                $module->comments_status = 0;
                $module->date_status = 1;
                $module->longtext_status = 1;
                $module->editor_status = 1;
                $module->multi_images_status = $type === 1 ? 1 : 0;
                $module->related_status = 1;
                $module->seo_status = 1;
                $module->code_status = 1;
                $module->no_status = 1;
                $module->seo_title_ar = $titleAr;
                $module->seo_title_en = $titleEn;
                $module->seo_url_slug_ar = $slug;
                $module->seo_url_slug_en = $slug;
                $module->created_by = 1;
            }

            $module->sections_status = $hasSections ? 1 : 0;
            $module->authors_status = $hasAuthors ? 1 : 0;
            $module->status = 1;
            $module->updated_by = 1;
            $module->save();

            if ($key === 'news') {
                $this->ensureCategories($module, [
                    ['أخبار اليمن', 'Yemen News', 'yemen-news'],
                    ['أخبار العلماء', 'Scholars News', 'scholars-news'],
                    ['مواقف وتصريحات', 'Positions & Statements', 'positions-statements'],
                    ['البيانات', 'Statements', 'statements'],
                    ['الفعاليات', 'Events', 'events'],
                ]);
            }

            if ($key === 'articles') {
                $this->ensureCategories($module, [
                    ['مقالات العلماء', 'Scholars Articles', 'scholars-articles'],
                    ['فضاء حر', 'Open Space', 'open-space'],
                    ['أبحاث', 'Research', 'research'],
                    ['أوراق عمل', 'Working Papers', 'working-papers'],
                    ['كلمات العلماء', 'Scholars Speeches', 'scholars-speeches'],
                    ['قضايا', 'Issues', 'issues'],
                    ['فتاوى', 'Fatwas', 'fatwas'],
                ]);
            }
        }
    }

    /** @param array<int, array{0: string, 1: string, 2: string}> $definitions */
    private function ensureCategories(WebmasterSection $module, array $definitions): void
    {
        foreach ($definitions as $index => [$titleAr, $titleEn, $slug]) {
            $category = Section::query()->firstOrNew([
                'webmaster_id' => $module->id,
                'title_ar' => $titleAr,
            ]);
            $category->father_id = 0;
            $category->row_no = $index + 1;
            $category->title_en = $titleEn;
            $category->details_ar ??= '';
            $category->details_en ??= '';
            $category->status = 1;
            $category->visits ??= 0;
            $category->seo_title_ar = $titleAr;
            $category->seo_title_en = $titleEn;
            $category->seo_description_ar ??= '';
            $category->seo_description_en ??= '';
            $category->seo_keywords_ar ??= '';
            $category->seo_keywords_en ??= '';
            $category->seo_url_slug_ar = $slug;
            $category->seo_url_slug_en = $slug;
            $category->created_by ??= 1;
            $category->updated_by = 1;
            $category->save();
        }
    }

    /** @return array<string, WebmasterSection> */
    private function resolveModules(): array
    {
        $definitions = [
            'news' => ['الأخبار', 'News'],
            'articles' => ['المقالات', 'Articles'],
            'photos' => ['الصور', 'Photos'],
            'scholars' => ['دليل العلماء', 'Scholars Directory'],
            'infographics' => ['إنفوجرافيك', 'Infographic'],
            'publications' => ['إصدارات', 'Publications'],
            'magazine' => ['مجلة المنبر اليمني', 'Al Minbar Al Yemeni Magazine'],
        ];

        $modules = [];

        foreach ($definitions as $key => [$arabic, $english]) {
            $module = WebmasterSection::query()
                ->where('title_ar', $arabic)
                ->orWhere('title_en', $english)
                ->first();

            if ($module) {
                $modules[$key] = $module;
            }
        }

        return $modules;
    }

    /** @return array<string, Author> */
    private function seedAuthors(): array
    {
        $definitions = [
            'editorial' => ['فريق تحرير المنبر اليمني', 'Al Minbar Editorial Team', 'فريق التحرير', 'Editorial team'],
            'alamri' => ['د. محمد بن موسى العامري', 'Dr. Mohammed Al-Amri', 'باحث وكاتب', 'Researcher and writer'],
            'program' => ['عضو برنامج التواصل مع علماء اليمن', 'Yemen Scholars Communication Program Member', 'كاتب وباحث', 'Writer and researcher'],
        ];

        $authors = [];

        foreach ($definitions as $key => [$nameAr, $nameEn, $positionAr, $positionEn]) {
            $author = Author::query()->firstOrNew(['name_ar' => $nameAr]);
            $author->name_en = $nameEn;
            $author->position_ar = $positionAr;
            $author->position_en = $positionEn;
            $author->status = 1;
            $author->created_by ??= 1;
            $author->updated_by = 1;
            $author->save();
            $authors[$key] = $author;
        }

        return $authors;
    }

    /**
     * @param  array<string, WebmasterSection>  $modules
     * @param  array<string, Author>  $authors
     */
    private function seedTopics(array $modules, array $authors): void
    {
        $demoImages = [
            'yh-news-research-competition' => 'yh-scholars-forum.jpg',
            'yh-news-scholars-unity' => 'yh-scholars-hub.jpg',
            'yh-news-training-courses' => 'yh-scholars-research.jpg',
            'yh-news-community-awareness' => 'yh-scholars-forum.jpg',
            'yh-news-knowledge-peace' => 'yh-scholars-research.jpg',
            'yh-news-responsible-speech' => 'yh-scholars-hub.jpg',
            'yh-article-optimism-realism' => 'yh-scholars-research.jpg',
            'yh-article-scholars-hadramout' => 'yh-scholars-forum.jpg',
            'yh-article-new-generations' => 'yh-scholars-hub.jpg',
            'yh-research-development-stability' => 'yh-scholars-research.jpg',
            'yh-issue-digital-rumors' => 'yh-scholars-forum.jpg',
            'yh-fatwa-news-verification' => 'yh-scholars-hub.jpg',
            'yh-paper-community-awareness' => 'yh-scholars-research.jpg',
            'yh-photo-scholars-gathering' => 'yh-scholars-forum.jpg',
            'yh-photo-training' => 'yh-scholars-research.jpg',
            'yh-photo-dialogue' => 'yh-scholars-hub.jpg',
            'yh-photo-cultural-event' => 'yh-scholars-forum.jpg',
            'yh-infographic-education' => 'yh-info-01.jpg',
            'yh-infographic-reconstruction' => 'yh-info-02.jpg',
            'yh-infographic-awareness' => 'yh-info-03.jpg',
            'yh-infographic-community' => 'yh-info-04.jpg',
            'yh-magazine-35' => 'yh-mag-36.jpg',
            'yh-magazine-34' => 'yh-mag-35.jpg',
            'yh-magazine-33' => 'yh-mag-34.jpg',
            'yh-magazine-32' => 'yh-mag-33.jpg',
        ];

        $records = [
            // News and positions
            ['news', 'أخبار اليمن', 1, 'الفائزون بالمسابقة البحثية الأولى حول التنمية والاستقرار', 'Winners of the First Research Competition on Development and Stability', 'yh-news-research-competition', '2026-09-18', 1,
                'إعلان نتائج مسابقة بحثية تناولت دور التنمية في تحقيق الاستقرار ومكافحة التطرف، ضمن نشاط معرفي يربط البحث العلمي بقضايا اليمن الراهنة.',
                'An announcement of a research competition exploring the role of development in stability and countering extremism.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],
            ['news', 'أخبار العلماء', 2, 'علماء اليمن يؤكدون أهمية توحيد الصف ومساندة مؤسسات الدولة', 'Yemeni Scholars Stress Unity and Support for State Institutions', 'yh-news-scholars-unity', '2026-09-16', 1,
                'موقف عام يؤكد مسؤولية العلماء في جمع الكلمة، وترسيخ السلم المجتمعي، ومواجهة الخطاب الذي يغذي الانقسام والعنف.',
                'A public position emphasizing scholars’ responsibility in unity, social peace, and confronting divisive rhetoric.',
                'https://www.alminbarye.com/article/ed64f536-4255-4e6e-9974-4b3209189243', 'مجلة المنبر اليمني', 'editorial'],
            ['news', 'الفعاليات', 3, 'برنامج التواصل مع علماء اليمن ينظم سلسلة دورات تدريبية', 'Training Courses Held for Yemeni Scholars and Preachers', 'yh-news-training-courses', '2026-09-12', 0,
                'سلسلة برامج تدريبية شارك فيها علماء ودعاة من محافظات يمنية ومدن سعودية، وركزت على تنمية المهارات الدعوية والمعرفية.',
                'A training series for scholars and preachers focused on developing communication and knowledge skills.',
                'https://www.alminbarye.com/article/0d810986-0246-4526-b6d4-0ea70b6bff60', 'مجلة المنبر اليمني', 'editorial'],
            ['news', 'مواقف وتصريحات', 4, 'دعوة إلى تعزيز دور العلماء في تحصين الوعي المجتمعي', 'A Call to Strengthen Scholars’ Role in Community Awareness', 'yh-news-community-awareness', '2026-09-09', 0,
                'دعوة إلى تكثيف البيان الشرعي الرصين، والرد على خطاب الغلو بالحجة، وبناء وعي مجتمعي يجمع بين المعرفة والاعتدال.',
                'A call for sound religious guidance, evidence-based responses to extremism, and balanced public awareness.',
                'https://www.alminbarye.com/article/f901b560-1208-47f0-bbe4-9bd3ce777c11', 'مجلة المنبر اليمني', 'editorial'],
            ['news', 'أخبار اليمن', 5, 'جلسة علمية تناقش أثر المعرفة في بناء السلام المجتمعي', 'A Scholarly Session Discusses Knowledge and Social Peace', 'yh-news-knowledge-peace', '2026-09-05', 0,
                'مادة تجريبية توضح شكل الأخبار والفعاليات عند نشرها في المنصة، ويمكن استبدالها مباشرة من لوحة التحكم.',
                'Demo copy illustrating how news and events appear on the platform; it can be replaced in the dashboard.',
                'https://www.alminbarye.com/news', 'صفحة الأخبار في مجلة المنبر اليمني', 'editorial'],
            ['news', 'الفعاليات', 6, 'ندوة فكرية حول مسؤولية الكلمة في زمن الأزمات', 'Forum on Responsible Public Speech During Crises', 'yh-news-responsible-speech', '2026-09-01', 0,
                'نموذج تحريري لخبر فعالية فكرية يعرض العنوان، والتصنيف، والتاريخ، والملخص في واجهة الموقع الجديدة.',
                'An editorial demo of an intellectual forum, showing title, category, date, and summary in the new layout.',
                'https://www.alminbarye.com/news', 'صفحة الأخبار في مجلة المنبر اليمني', 'editorial'],

            // Articles and research
            ['articles', 'مقالات العلماء', 1, 'بين التفاؤل والواقعية', 'Between Optimism and Realism', 'yh-article-optimism-realism', '2026-09-15', 1,
                'قراءة في حاجة الداعية والمتلقي إلى الجمع بين بث الأمل وفهم الواقع، والرجوع إلى أهل الخبرة عند التعامل مع الأخبار والأزمات.',
                'A reflection on balancing hope with an informed reading of reality and consulting expertise during crises.',
                'https://www.alminbarye.com/article/727b6a40-758e-4177-a4f2-fcecae21a8ea', 'مجلة المنبر اليمني', 'program'],
            ['articles', 'مقالات العلماء', 2, 'العلماء ودورهم في وأد الفتنة في حضرموت', 'Scholars and Their Role in Preventing Discord in Hadramout', 'yh-article-scholars-hadramout', '2026-07-27', 1,
                'يتناول المقال مسؤولية العلماء والدعاة في البيان والنصح، ومواجهة الأفكار المنحرفة، والإسهام في حماية المجتمع من الفتن.',
                'The article considers scholars’ duty in guidance, counsel, confronting deviant ideas, and protecting social cohesion.',
                'https://www.alminbarye.com/articles', 'مجلة المنبر اليمني', 'alamri'],
            ['articles', 'فضاء حر', 3, 'قراءة في الخطاب الموجّه إلى الأجيال الجديدة', 'Reading Public Discourse Addressed to New Generations', 'yh-article-new-generations', '2026-07-24', 0,
                'مقال تجريبي يوضح قالب فضاء حر المخصص للباحثين والكتّاب، مع مساحة لعرض الفكرة والكاتب وتاريخ النشر بصورة واضحة.',
                'Demo copy for the open-opinion section, designed to highlight the idea, writer, and publication date.',
                'https://www.alminbarye.com/articles', 'صفحة المقالات في مجلة المنبر اليمني', 'editorial'],
            ['articles', 'أبحاث', 4, 'دور التنمية في تعزيز الاستقرار ومواجهة التطرف', 'Development, Stability, and Countering Extremism', 'yh-research-development-stability', '2026-07-20', 0,
                'ملخص تجريبي لورقة بحثية تربط تحسين الخدمات وفرص التعليم والعمل بتعزيز الاستقرار والوقاية من خطاب التطرف.',
                'A demo research abstract linking services, education, and employment opportunities with stability and prevention.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],
            ['articles', 'قضايا', 5, 'المعرفة الرصينة في مواجهة الشائعات الرقمية', 'Reliable Knowledge in the Face of Digital Rumors', 'yh-issue-digital-rumors', '2026-07-18', 0,
                'معالجة تحريرية تجريبية لقضية معاصرة، تبرز أهمية التثبت من المصادر وبناء المحتوى المسؤول في البيئة الرقمية.',
                'An editorial demo on source verification and responsible content in the digital environment.',
                'https://www.alminbarye.com/articles', 'صفحة المقالات في مجلة المنبر اليمني', 'editorial'],
            ['articles', 'فتاوى', 6, 'ضوابط التعامل مع الأخبار والشائعات في الفضاء الرقمي', 'Guidelines for Dealing with News and Rumors Online', 'yh-fatwa-news-verification', '2026-07-15', 0,
                'مادة تجريبية لقسم الفتاوى توضح أهمية التثبت، والرجوع إلى أهل الاختصاص، وتجنب نشر الأخبار قبل التحقق منها.',
                'A demo fatwa entry about verification, consulting specialists, and avoiding unverified claims.',
                'https://www.alminbarye.com/articles', 'صفحة المقالات في مجلة المنبر اليمني', 'editorial'],
            ['articles', 'أوراق عمل', 7, 'تعزيز حضور العلماء في بناء الوعي المجتمعي', 'Strengthening Scholars’ Role in Public Awareness', 'yh-paper-community-awareness', '2026-07-12', 0,
                'ورقة عمل تجريبية تستعرض مسارات عملية لتطوير الخطاب العلمي وتعزيز أثره الإيجابي في المجتمع.',
                'A demo working paper outlining practical ways to strengthen scholarly communication and social impact.',
                'https://www.alminbarye.com/articles', 'صفحة المقالات في مجلة المنبر اليمني', 'editorial'],

            // Scholars directory
            ['scholars', null, 1, 'الإمام محمد بن علي الشوكاني', 'Imam Muhammad ibn Ali al-Shawkani', 'yh-scholar-al-shawkani', null, 1,
                'عالم يمني بارز من أعلام القرن الثالث عشر الهجري، عُرف باشتغاله بالحديث والفقه والتفسير، وترك تراثًا علميًا واسعًا.',
                'A prominent Yemeni scholar known for major contributions to hadith, jurisprudence, and Quranic exegesis.',
                'https://www.alminbarye.com/article/c801ce7d-cbe2-4489-90f1-344dca9d9ca9', 'مجلة المنبر اليمني', null, ['التخصص' => 'الحديث والفقه والتفسير', 'القرن' => 'الثالث عشر الهجري']],
            ['scholars', null, 2, 'العلامة محمد بن إسماعيل الأمير الصنعاني', 'Scholar Muhammad ibn Ismail al-Sanani', 'yh-scholar-al-sanani', null, 1,
                'محدّث وفقيه يمني، من أشهر آثاره كتاب سبل السلام، وله حضور بارز في الدرس الحديثي والفقهي في اليمن.',
                'A Yemeni hadith scholar and jurist, widely known as the author of Subul al-Salam.',
                'https://waqfeya.net/books/%D8%B3%D8%A8%D9%84-%D8%A7%D9%84%D8%B3%D9%84%D8%A7%D9%85-%D8%AA-%D8%A7%D9%84%D8%B5%D8%A8%D8%A7%D8%A8%D8%B7%D9%8A-%D9%88%D8%A7%D9%84%D8%B3%D9%8A%D8%AF/ebdc1210a3b7419780424c1edd20b035', 'المكتبة الوقفية', null, ['التخصص' => 'الحديث والفقه', 'القرن' => 'الثاني عشر الهجري']],
            ['scholars', null, 3, 'العلامة مرشد الشهاب', 'Scholar Murshid Al-Shihab', 'yh-scholar-murshid-alshihab', null, 0,
                'ترجمة موجزة لعالم يمني تسلط الضوء على مسيرته العلمية والدعوية، وتتيح ربط مؤلفاته ومقالاته ومواده في صفحة واحدة.',
                'A concise profile of a Yemeni scholar, designed to connect his biography, writings, and media in one place.',
                'https://www.alminbarye.com/article/adc6a622-a324-4131-a307-254eef78767c', 'مجلة المنبر اليمني', null, ['التخصص' => 'العلوم الشرعية والدعوة', 'القرن' => 'الخامس عشر الهجري']],
            ['scholars', null, 4, 'د. محمد بن موسى العامري', 'Dr. Mohammed ibn Musa Al-Amri', 'yh-scholar-mohammed-alamri', null, 0,
                'باحث وكاتب يمني، تظهر له مقالات تتناول دور العلماء في معالجة القضايا العامة وتعزيز الوعي المجتمعي.',
                'A Yemeni researcher and writer whose articles discuss scholars’ role in public issues and social awareness.',
                'https://www.alminbarye.com/articles', 'مجلة المنبر اليمني', null, ['التخصص' => 'الفكر والدعوة', 'القرن' => 'الخامس عشر الهجري']],

            // Photos / visual coverage
            ['photos', null, 1, 'لقاء علمي يجمع نخبة من علماء ودعاة اليمن', 'A Scholarly Gathering of Yemeni Scholars and Preachers', 'yh-photo-scholars-gathering', '2026-09-14', 1,
                'معرض تجريبي لتغطية اللقاءات العلمية والفعاليات الدعوية بصياغة بصرية هادئة.', 'A visual demo for scholarly gatherings and educational events.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],
            ['photos', null, 2, 'من أجواء الدورات التدريبية للعلماء والدعاة', 'From Training Sessions for Scholars and Preachers', 'yh-photo-training', '2026-09-10', 0,
                'نموذج لألبوم صور يوثق برامج التدريب وبناء القدرات.', 'A sample photo story documenting training and capacity-building programs.',
                'https://www.alminbarye.com/article/0d810986-0246-4526-b6d4-0ea70b6bff60', 'مجلة المنبر اليمني', 'editorial'],
            ['photos', null, 3, 'جلسة حوارية حول قضايا المجتمع اليمني', 'Dialogue Session on Yemeni Social Issues', 'yh-photo-dialogue', '2026-09-06', 0,
                'قالب تجريبي لعرض التغطيات المصورة في الصفحة الرئيسية.', 'A demo visual-story card for the homepage.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],
            ['photos', null, 4, 'مشاهد من فعالية فكرية وثقافية', 'Scenes from an Intellectual and Cultural Event', 'yh-photo-cultural-event', '2026-09-02', 0,
                'محتوى افتراضي قابل للاستبدال من لوحة التحكم.', 'Editable placeholder content for the dashboard.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],

            // Infographics
            ['infographics', null, 1, 'الدعم السعودي لقطاع التعليم في اليمن', 'Saudi Support for Education in Yemen', 'yh-infographic-education', '2026-09-17', 1,
                'عنوان تجريبي لإنفوجرافيك يلخص مبادرات ومؤشرات دعم قطاع التعليم.', 'A demo infographic title summarizing education support initiatives.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],
            ['infographics', null, 2, 'مشاريع البرنامج السعودي لتنمية وإعمار اليمن', 'Saudi Development and Reconstruction Projects in Yemen', 'yh-infographic-reconstruction', '2026-09-11', 0,
                'بطاقة بصرية موجزة لعرض أرقام المشاريع وقطاعاتها بصورة سهلة القراءة.', 'A concise visual card for project figures and sectors.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],
            ['infographics', null, 3, 'مرتكزات بناء الوعي ومواجهة الشائعات', 'Foundations of Awareness and Countering Rumors', 'yh-infographic-awareness', '2026-09-07', 0,
                'نموذج إنفوجرافيك توعوي قابل للتحرير وإضافة التصميم النهائي إليه.', 'An editable awareness infographic placeholder.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],
            ['infographics', null, 4, 'العلماء في خدمة المجتمع', 'Scholars in Service of Society', 'yh-infographic-community', '2026-09-03', 0,
                'تصور بصري موجز لأدوار العالم في التعليم والإرشاد والإصلاح.', 'A visual overview of scholars’ educational, guidance, and reconciliation roles.',
                'https://www.alminbarye.com/', 'مجلة المنبر اليمني', 'editorial'],

            // Publications
            ['publications', null, 1, 'نيل الأوطار شرح منتقى الأخبار', 'Nayl al-Awtar', 'yh-book-nayl-alawtar', null, 1,
                'من أشهر مؤلفات الإمام الشوكاني في شرح أحاديث الأحكام ومناقشة مسائل الفقه المقارن.', 'A major work by al-Shawkani on hadiths of legal rulings and comparative jurisprudence.',
                'https://shamela.org/gen/e77265cc796f523b7911de7a57c24f0e_gen.pdf', 'المكتبة الشاملة', null],
            ['publications', null, 2, 'سبل السلام شرح بلوغ المرام', 'Subul al-Salam', 'yh-book-subul-alsalam', null, 1,
                'شرح حديثي فقهي لمحمد بن إسماعيل الأمير الصنعاني على كتاب بلوغ المرام.', 'A hadith and jurisprudence commentary by Muhammad ibn Ismail al-Sanani.',
                'https://waqfeya.net/books/%D8%B3%D8%A8%D9%84-%D8%A7%D9%84%D8%B3%D9%84%D8%A7%D9%85-%D8%AA-%D8%A7%D9%84%D8%B5%D8%A8%D8%A7%D8%A8%D8%B7%D9%8A-%D9%88%D8%A7%D9%84%D8%B3%D9%8A%D8%AF/ebdc1210a3b7419780424c1edd20b035', 'المكتبة الوقفية', null],
            ['publications', null, 3, 'فتح القدير الجامع بين فني الرواية والدراية', 'Fath al-Qadir', 'yh-book-fath-alqadir', null, 0,
                'تفسير للإمام الشوكاني يجمع بين الرواية والدراية، ويعرض الأقوال مع المناقشة والترجيح.', 'Al-Shawkani’s Quranic exegesis combining transmitted reports with analytical interpretation.',
                'https://waqfeya.net/books/%D9%81%D8%AA%D8%AD-%D8%A7%D9%84%D9%82%D8%AF%D9%8A%D8%B1-%D8%A7%D9%84%D8%AC%D8%A7%D9%85%D8%B9-%D8%A8%D9%8A%D9%86-%D9%81%D9%86%D9%8A-%D8%A7%D9%84%D8%B1%D9%88%D8%A7%D9%8A%D8%A9-%D9%88%D8%A7%D9%84%D8%AF%D8%B1%D8%A7%D9%8A%D8%A9-%D9%85%D9%86-%D8%B9%D9%84%D9%85-%D8%A7%D9%84%D8%AA%D9%81%D8%B3%D9%8A%D8%B1-%D8%AA%D9%81%D8%B3%D9%8A%D8%B1-%D8%A7%D9%84%D8%B4%D9%88%D9%83%D8%A7%D9%86%D9%8A-%D8%B7-%D8%A7%D9%84%D8%A3%D9%88%D9%82%D8%A7%D9%81-%D8%A7%D9%84%D8%B3%D8%B9%D9%88%D8%AF%D9%8A%D8%A9-e9808fae153744f2ac122e5745cb5c49', 'المكتبة الوقفية', null],
            ['publications', null, 4, 'دليل الباحث إلى موضوعات مجلة المنبر اليمني', 'Researcher’s Guide to Al Minbar Magazine', 'yh-book-magazine-guide', null, 0,
                'إصدار تجريبي يوضح شكل بطاقة الكتب والإصدارات، ويمكن ربطه بملف أو ملخص لاحقًا.', 'A demo publication showing the book-card layout and future attachment support.',
                'https://www.alminbarye.com/magazine', 'أرشيف مجلة المنبر اليمني', 'editorial'],

            // Magazine
            ['magazine', null, 1, 'العدد 35: حضرموت عبق التاريخ وبوابة المستقبل', 'Issue 35: Hadramout, Heritage and Gateway to the Future', 'yh-magazine-35', '2026-09-01', 1,
                'عدد خاص يقدّم ملفًا عن حضرموت وتاريخها ومكانتها وآفاقها المستقبلية، ضمن قالب المجلة الدوري.', 'A special issue on Hadramout, its heritage, position, and future prospects.',
                'https://www.alminbarye.com/magazine', 'أرشيف مجلة المنبر اليمني', 'editorial', ['رابط العدد' => 'https://www.alminbarye.com/magazine']],
            ['magazine', null, 2, 'العدد 34 من مجلة المنبر اليمني', 'Issue 34 of Al Minbar Al Yemeni', 'yh-magazine-34', '2026-08-01', 0,
                'نموذج لعدد سابق ضمن أرشيف المجلة، وتظهر تحته موضوعاته عند إضافة الربط من لوحة التحكم.', 'A sample archived issue whose articles can be linked from the dashboard.',
                'https://www.alminbarye.com/magazine', 'أرشيف مجلة المنبر اليمني', 'editorial', ['رابط العدد' => 'https://www.alminbarye.com/magazine']],
            ['magazine', null, 3, 'العدد 33 من مجلة المنبر اليمني', 'Issue 33 of Al Minbar Al Yemeni', 'yh-magazine-33', '2026-07-01', 0,
                'مدخل تجريبي لاستكمال شكل أرشيف الأعداد في الصفحة الرئيسية.', 'A demo entry completing the magazine archive layout on the homepage.',
                'https://www.alminbarye.com/magazine', 'أرشيف مجلة المنبر اليمني', 'editorial', ['رابط العدد' => 'https://www.alminbarye.com/magazine']],
            ['magazine', null, 4, 'العدد 32 من مجلة المنبر اليمني', 'Issue 32 of Al Minbar Al Yemeni', 'yh-magazine-32', '2026-06-01', 0,
                'عدد أرشيفي تجريبي قابل للاستبدال بالبيانات والملف النهائي.', 'An editable demo archive issue.',
                'https://www.alminbarye.com/magazine', 'أرشيف مجلة المنبر اليمني', 'editorial', ['رابط العدد' => 'https://www.alminbarye.com/magazine']],
        ];

        foreach ($records as $record) {
            [$moduleKey, $categoryTitle, $rowNo, $titleAr, $titleEn, $slugEn, $date, $featured,
                $summaryAr, $summaryEn, $sourceUrl, $sourceName, $authorKey] = array_pad($record, 14, null);
            $customFields = $record[14] ?? [];

            if (! isset($modules[$moduleKey])) {
                continue;
            }

            $module = $modules[$moduleKey];
            $category = $categoryTitle ? $this->findCategory($module->id, $categoryTitle) : null;
            $detailsAr = $this->detailsWithSource($summaryAr, $sourceUrl, $sourceName, 'المصدر');
            $detailsEn = $this->detailsWithSource($summaryEn, $sourceUrl, $sourceName, 'Source');

            $topic = Topic::query()->firstOrNew([
                'webmaster_id' => $module->id,
                'seo_url_slug_en' => $slugEn,
            ]);

            $topic->row_no = $rowNo;
            $topic->section_id = $category?->id;
            $topic->title_ar = $titleAr;
            $topic->title_en = $titleEn;
            $topic->details_ar = $detailsAr;
            $topic->details_en = $detailsEn;
            $topic->date = $date;
            $topic->photo_file = $demoImages[$slugEn] ?? $topic->photo_file;
            $topic->status = 1;
            $topic->featured = $featured;
            $topic->seo_title_ar = $titleAr.' | المنبر الإعلامي لعلماء اليمن';
            $topic->seo_title_en = $titleEn.' | Yemen Scholars Media Platform';
            $topic->seo_description_ar = mb_substr($summaryAr, 0, 155);
            $topic->seo_description_en = mb_substr($summaryEn, 0, 155);
            $topic->seo_keywords_ar = 'علماء اليمن، المنبر اليمني، '.$titleAr;
            $topic->seo_keywords_en = 'Yemen scholars, Al Minbar, '.$titleEn;
            $topic->seo_url_slug_ar = $slugEn;
            $topic->created_by ??= 1;
            $topic->updated_by = 1;
            $topic->save();

            if ($category) {
                TopicCategory::query()->firstOrCreate([
                    'topic_id' => $topic->id,
                    'section_id' => $category->id,
                ]);
            }

            if ($authorKey && isset($authors[$authorKey])) {
                DB::table('author_topic')->updateOrInsert([
                    'author_id' => $authors[$authorKey]->id,
                    'topic_id' => $topic->id,
                ], [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            $this->syncCustomFields($topic, $module, $customFields);
        }
    }

    private function findCategory(int $moduleId, string $title): ?Section
    {
        $category = Section::query()
            ->where('webmaster_id', $moduleId)
            ->where('title_ar', $title)
            ->first();

        if ($category || ! in_array($title, ['فتاوى', 'أوراق عمل'], true)) {
            return $category;
        }

        $englishTitles = ['فتاوى' => 'Fatwas', 'أوراق عمل' => 'Working Papers'];
        $slugs = ['فتاوى' => 'fatwas', 'أوراق عمل' => 'working-papers'];

        $category = new Section;
        $category->webmaster_id = $moduleId;
        $category->father_id = 0;
        $category->row_no = ((int) Section::query()->where('webmaster_id', $moduleId)->max('row_no')) + 1;
        $category->title_ar = $title;
        $category->title_en = $englishTitles[$title];
        $category->details_ar = '';
        $category->details_en = '';
        $category->status = 1;
        $category->visits = 0;
        $category->seo_title_ar = $title;
        $category->seo_title_en = $englishTitles[$title];
        $category->seo_description_ar = '';
        $category->seo_description_en = '';
        $category->seo_keywords_ar = '';
        $category->seo_keywords_en = '';
        $category->seo_url_slug_ar = $slugs[$title];
        $category->seo_url_slug_en = $slugs[$title];
        $category->created_by = 1;
        $category->updated_by = 1;
        $category->save();

        return $category;
    }

    private function syncDemoImages(): void
    {
        $sourceDirectory = base_path('../assets/modern/images/demo-content');
        $configuredUploadsPath = trim((string) config('filesystems.uploads_path'));
        $uploadsDirectory = $configuredUploadsPath !== ''
            && ! preg_match('#^https?://#i', $configuredUploadsPath)
                ? $configuredUploadsPath
                : base_path('../uploads');
        $targetDirectory = rtrim($uploadsDirectory, '/\\').DIRECTORY_SEPARATOR.'topics';

        if (! File::isDirectory($sourceDirectory)) {
            return;
        }

        File::ensureDirectoryExists($targetDirectory);

        foreach (File::files($sourceDirectory) as $image) {
            File::copy($image->getPathname(), $targetDirectory.DIRECTORY_SEPARATOR.$image->getFilename());
        }
    }

    /** @param array<string, string> $values */
    private function syncCustomFields(Topic $topic, WebmasterSection $module, array $values): void
    {
        foreach ($values as $fieldTitle => $value) {
            $field = WebmasterSectionField::query()
                ->where('webmaster_id', $module->id)
                ->where('title_ar', $fieldTitle)
                ->first();

            if (! $field) {
                continue;
            }

            $topicField = TopicField::query()->firstOrNew([
                'topic_id' => $topic->id,
                'field_id' => $field->id,
            ]);
            $topicField->field_value = $value;
            $topicField->save();
        }
    }

    private function detailsWithSource(string $summary, string $url, string $name, string $label): string
    {
        $note = $label === 'Source'
            ? 'Editable demo summary.'
            : 'ملخص تحريري تجريبي قابل للتعديل.';

        return '<p>'.e($summary).'</p><p><small><strong>'.e($label).':</strong> '
            .'<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">'.e($name).'</a>'
            .' — '.e($note).'</small></p>';
    }

    /** @param array<string, WebmasterSection> $modules */
    private function seedLandingBlocks(array $modules): void
    {
        $settings = WebmasterSetting::query()->first();
        $landingPage = $settings?->landing_page_id ? Topic::find($settings->landing_page_id) : null;

        if (! $landingPage) {
            $landingPage = Topic::query()->where('title_ar', 'الصفحة الرئيسية')->first();
        }

        if (! $landingPage) {
            $this->command?->warn('Yemen Scholars demo seeder: landing page was not found; content was seeded without TopicBlocks.');

            return;
        }

        TopicBlock::query()
            ->where('topic_id', $landingPage->id)
            ->whereIn('block_name', [
                'Welcome Message', 'Text Banners', 'قطاعات المجموعة', 'قدراتنا وإمكاناتنا',
                'Latest Articles', 'Our Staffs', 'Photo Gallary', 'FAQ', 'Testimonials', 'Partners',
            ])
            ->update(['status' => 0, 'updated_by' => 1]);

        $definitions = [
            ['YH: Scholars', 10, 'scholars', 'YhScholars', 'yh-topic-block yh-topic-block--scholars', 'دليل العلماء', 'Scholars Directory', 'موسوعة لسير علماء اليمن وإنتاجهم العلمي والفكري والمرئي في ملفات معرفية مترابطة.', 'An encyclopedia of Yemeni scholars, their biographies, and scholarly work.', 8, 1, []],
            ['YH: Newsroom', 20, 'news', 'YhNewsroom', 'yh-topic-block yh-topic-block--newsroom', 'الأخبار والمواقف', 'News & Positions', 'أخبار العلماء ومواقفهم وبياناتهم والفعاليات الدعوية والدينية والفكرية.', 'Scholars’ news, positions, statements, and intellectual events.', 6, 1, []],
            ['YH: Opinions', 30, 'articles', 'YhOpinions', 'yh-topic-block yh-topic-block--opinions', 'مقالات وأبحاث', 'Articles & Research', 'رؤى العلماء والباحثين وقراءات معمقة للقضايا الراهنة.', 'Scholarly opinions, research, and considered perspectives on current issues.', 12, 1, ['slider_columns' => 3, 'slider_autoplay' => 1, 'slider_speed' => 5000]],
            ['YH: Infographics', 40, 'infographics', 'YhVisuals', 'yh-topic-block yh-topic-block--infographics yh-visuals--infographic', 'إنفوجرافيك', 'Infographics', 'الأفكار والأرقام في تصاميم موجزة سهلة المشاركة.', 'Ideas and figures presented in concise, shareable visuals.', 6, 1, ['eyebrow_ar' => 'البيانات في صورة', 'label_ar' => 'إنفوجرافيك']],
            ['YH: Visual Stories', 50, 'photos', 'YhVisuals', 'yh-topic-block yh-topic-block--visual-stories', 'المعرض المرئي', 'Visual Stories', 'تغطيات بصرية مختارة من اللقاءات والفعاليات العلمية.', 'Selected visual coverage of scholarly gatherings and events.', 6, 1, ['eyebrow_ar' => 'شاشة المنبر', 'label_ar' => 'تغطية مصورة']],
            ['YH: Publications', 60, 'publications', 'YhBooks', 'yh-topic-block yh-topic-block--publications', 'إصدارات ومكتبة', 'Publications & Library', 'كتب وإصدارات مختارة من تراث علماء اليمن ونتاجهم العلمي.', 'Selected books and publications from Yemen’s scholarly heritage.', 4, 1, []],
            ['YH: Magazine', 70, 'magazine', 'YhMagazine', 'yh-topic-block yh-topic-block--magazine', 'مجلة المنبر اليمني', 'Al Minbar Al Yemeni Magazine', 'الأعداد والملفات والموضوعات الخاصة في مساحة واحدة.', 'Issues, special files, and magazine features in one place.', 4, 2, []],
        ];

        foreach ($definitions as [$name, $rowNo, $moduleKey, $view, $css, $titleAr, $titleEn, $descAr, $descEn, $count, $order, $extra]) {
            if (! isset($modules[$moduleKey])) {
                continue;
            }

            $block = TopicBlock::query()->firstOrNew([
                'topic_id' => $landingPage->id,
                'block_name' => $name,
            ]);
            $block->row_no = $rowNo;
            $block->type = 3;
            $block->title_status = 1;
            $block->desc_status = 1;
            $block->image_status = 0;
            $block->divider_status = 0;
            $block->more_btn_status = 1;
            $block->css_classes = $css;
            $block->status = 1;
            $block->content = json_encode(array_merge([
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'desc_ar' => $descAr,
                'desc_en' => $descEn,
                'bg_ar' => null,
                'bg_en' => null,
                'module_id' => $modules[$moduleKey]->id,
                'category_ids' => '',
                'records_count' => $count,
                'records_order' => $order,
                'view_style' => $view,
            ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $block->created_by ??= 1;
            $block->updated_by = 1;
            $block->save();
        }
    }
}

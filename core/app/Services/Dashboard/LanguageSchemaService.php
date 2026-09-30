<?php

namespace App\Services\Dashboard;

use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\Country;
use App\Models\Language;
use App\Models\Map;
use App\Models\Menu;
use App\Models\Permissions;
use App\Models\Popup;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\WebmasterBanner;
use App\Models\WebmasterSection;
use App\Models\WebmasterSectionField;
use Closure;
use Helper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LanguageSchemaService
{
    private const LANGUAGE_TABLES = [
        [
            'table' => 'topics',
            'model' => Topic::class,
            'columns' => [
                ['title', 'string'],
                ['details', 'longText'],
                ['seo_title', 'string'],
                ['seo_description', 'text'],
                ['seo_keywords', 'text'],
                ['seo_url_slug', 'string'],
                ['photo_alt', 'string'],
            ],
            'copy' => ['title', 'details', 'seo_title', 'seo_description', 'seo_keywords'],
        ],
        [
            'table' => 'sections',
            'model' => Section::class,
            'columns' => [
                ['title', 'string'],
                ['details', 'text'],
                ['seo_title', 'string'],
                ['seo_description', 'text'],
                ['seo_keywords', 'text'],
                ['seo_url_slug', 'string'],
            ],
            'copy' => ['title', 'details', 'seo_title', 'seo_description', 'seo_keywords'],
        ],
        [
            'table' => 'menus',
            'model' => Menu::class,
            'columns' => [
                ['title', 'string'],
                ['link', 'string'],
            ],
            'copy' => ['title', 'link'],
        ],
        [
            'table' => 'maps',
            'model' => Map::class,
            'columns' => [
                ['title', 'string'],
                ['details', 'text'],
            ],
            'copy' => ['title', 'details'],
        ],
        [
            'table' => 'countries',
            'model' => Country::class,
            'columns' => [
                ['title', 'string'],
            ],
            'copy' => ['title'],
            'keep_english' => true,
        ],
        [
            'table' => 'banners',
            'model' => Banner::class,
            'columns' => [
                ['title', 'string'],
                ['details', 'text'],
                ['file', 'string'],
                ['link', 'string'],
            ],
            'copy' => ['title', 'details', 'link'],
        ],
        [
            'table' => 'popups',
            'model' => Popup::class,
            'columns' => [
                ['title', 'string'],
                ['details', 'longText'],
            ],
            'copy' => ['title', 'details'],
        ],
        [
            'table' => 'attach_files',
            'model' => AttachFile::class,
            'columns' => [
                ['title', 'string'],
            ],
            'copy' => ['title'],
        ],
        [
            'table' => 'webmaster_section_fields',
            'model' => WebmasterSectionField::class,
            'columns' => [
                ['title', 'string'],
                ['details', 'text'],
            ],
            'copy' => ['title', 'details'],
        ],
        [
            'table' => 'webmaster_sections',
            'model' => WebmasterSection::class,
            'columns' => [
                ['title', 'string'],
                ['seo_title', 'string'],
                ['seo_description', 'text'],
                ['seo_keywords', 'text'],
                ['seo_url_slug', 'string'],
            ],
            'copy' => ['title', 'seo_title', 'seo_description', 'seo_keywords'],
        ],
        [
            'table' => 'webmaster_banners',
            'model' => WebmasterBanner::class,
            'columns' => [
                ['title', 'string'],
            ],
            'copy' => ['title'],
        ],
        [
            'table' => 'settings',
            'model' => Setting::class,
            'columns' => [
                ['site_title', 'text'],
                ['site_desc', 'text'],
                ['site_keywords', 'text'],
                ['contact_t1', 'text'],
                ['contact_t7', 'text'],
                ['style_logo', 'text'],
            ],
            'copy' => ['site_title', 'site_desc', 'site_keywords', 'contact_t1', 'contact_t7'],
        ],
        [
            'table' => 'permissions',
            'model' => Permissions::class,
            'columns' => [
                ['home_details', 'string'],
            ],
            'copy' => ['home_details'],
        ],
    ];

    public function normalizeLanguageCode($code): ?string
    {
        $code = trim(strtolower((string) $code));

        return preg_match('/^[a-z]{2}$/', $code) ? $code : null;
    }

    public function addLanguage($code): bool
    {
        $code = $this->normalizeLanguageCode($code);
        if ($code === null) {
            return false;
        }

        $currentLanguageCode = $this->normalizeLanguageCode(@Helper::currentLanguage()->code);

        foreach (self::LANGUAGE_TABLES as $definition) {
            $this->attempt('add language columns', $definition['table'], $code, function () use ($definition, $code, $currentLanguageCode) {
                Schema::table($definition['table'], function (Blueprint $table) use ($definition, $code) {
                    foreach ($definition['columns'] as [$prefix, $type]) {
                        $this->addLanguageColumn($table, $definition['table'], $prefix, $code, $type);
                    }
                });

                if ($currentLanguageCode !== null && $currentLanguageCode !== '') {
                    $this->copyLanguageColumns($definition['model'], $definition['copy'], $currentLanguageCode, $code);
                }
            });
        }

        $this->truncateTranslationCache($code);

        return true;
    }

    public function destroyLanguage($code): bool
    {
        $code = $this->normalizeLanguageCode($code);
        if ($code === null) {
            return false;
        }

        $currentLanguageCode = $this->normalizeLanguageCode(@Helper::currentLanguage()->code);
        if ($currentLanguageCode === $code) {
            $this->attempt('switch active language session', 'languages', $code, function () {
                $defaultLanguage = Language::first();
                if (!empty($defaultLanguage)) {
                    session(['dashboard_locale' => $defaultLanguage->code]);
                }
            });
        }

        foreach (self::LANGUAGE_TABLES as $definition) {
            if (($definition['keep_english'] ?? false) && $code === 'en') {
                continue;
            }

            $this->attempt('drop language columns', $definition['table'], $code, function () use ($definition, $code) {
                Schema::table($definition['table'], function (Blueprint $table) use ($definition, $code) {
                    foreach ($definition['columns'] as [$prefix]) {
                        $this->dropLanguageColumn($table, $definition['table'], $prefix, $code);
                    }
                });
            });
        }

        $this->truncateTranslationCache($code);

        return true;
    }

    private function schemaColumn(string $prefix, string $code): string
    {
        return $prefix.'_'.$code;
    }

    private function addLanguageColumn(Blueprint $table, string $tableName, string $prefix, string $code, string $type): void
    {
        $column = $this->schemaColumn($prefix, $code);
        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        match ($type) {
            'longText' => $table->longText($column)->nullable(),
            'text' => $table->text($column)->nullable(),
            default => $table->string($column)->nullable(),
        };
    }

    private function dropLanguageColumn(Blueprint $table, string $tableName, string $prefix, string $code): void
    {
        $column = $this->schemaColumn($prefix, $code);
        if (Schema::hasColumn($tableName, $column)) {
            $table->dropColumn($column);
        }
    }

    private function copyLanguageColumns(string $modelClass, array $prefixes, string $sourceCode, string $targetCode): void
    {
        foreach ($prefixes as $prefix) {
            $sourceColumn = $this->schemaColumn($prefix, $sourceCode);
            $targetColumn = $this->schemaColumn($prefix, $targetCode);
            $modelClass::where('id', '>', 0)->update([
                $targetColumn => DB::raw($sourceColumn),
            ]);
        }
    }

    private function truncateTranslationCache(string $code): void
    {
        $this->attempt('truncate translation cache', 'ltm_translations', $code, function () {
            DB::table('ltm_translations')->truncate();
        });
    }

    private function attempt(string $operation, string $table, string $code, Closure $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('Language schema operation failed.', [
                'operation' => $operation,
                'table' => $table,
                'language_code' => $code,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }
    }
}

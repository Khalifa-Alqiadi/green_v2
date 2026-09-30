<?php

namespace App\Services\Dashboard;

use App\Helpers\GoogleIndexing;
use App\Models\Permissions;
use App\Models\Topic;
use App\Models\User;
use App\Models\WebmasterSection;
use App\Models\WebmasterSectionField;
use App\Services\Ai\ModuleDefinitionValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleCreationService
{
    public function __construct(private readonly ModuleDefinitionValidator $validator) {}

    public function createFromDefinition(array $proposal, User $user): WebmasterSection
    {
        $section = DB::transaction(function () use ($proposal, $user) {
            WebmasterSection::query()->lockForUpdate()->get(['id']);
            foreach ($proposal['custom_fields'] as $definition) {
                if ((int) ($definition['type'] ?? -1) === 18 && filled($definition['default_value'] ?? null)) {
                    Topic::query()->whereKey((int) $definition['default_value'])->lockForUpdate()->first();
                }
            }
            $proposal = $this->validator->validate($proposal, $user);
            $section = $this->createModule($proposal, $user);
            foreach ($proposal['custom_fields'] as $index => $definition) {
                $this->createField($section, $definition + ['row_no' => $index + 1], $user);
            }
            $this->grantSectionAccess($section, $user);

            return $section;
        });

        $this->attemptIndexAfterCreate($section);

        return $section;
    }

    public function createManual(array $definition, User $user, ?string $photo = null): WebmasterSection
    {
        $section = DB::transaction(function () use ($definition, $user, $photo) {
            $section = $this->createModule($definition, $user, $photo);
            $this->grantSectionAccess($section, $user);

            return $section;
        });
        $this->attemptIndexAfterCreate($section);

        return $section;
    }

    public function createModule(array $definition, User $user, ?string $photo = null): WebmasterSection
    {
        $section = new WebmasterSection;
        $section->row_no = ((int) WebmasterSection::query()->max('row_no')) + 1;
        foreach ((array) $definition['titles'] as $code => $title) {
            $section->{'title_'.$code} = strip_tags((string) $title);
            $section->{'seo_url_slug_'.$code} = \Helper::URLSlug(strip_tags((string) $title), 'section', 0);
        }
        $section->type = (int) $definition['module_type'];
        $section->sections_status = (bool) $definition['sections_status'];
        $this->applyStatuses($section, $definition);
        $section->popup_id = $definition['popup_id'] ?? null;
        $section->status = 1;
        $section->photo = $photo;
        $section->created_by = $user->id;
        $section->save();

        return $section;
    }

    public function createField(WebmasterSection $section, array $definition, User $user): WebmasterSectionField
    {
        $field = new WebmasterSectionField;
        $field->webmaster_id = $section->id;
        $field->row_no = $definition['row_no'] ?? (((int) WebmasterSectionField::query()->where('webmaster_id', $section->id)->max('row_no')) + 1);
        foreach ((array) $definition['labels'] as $code => $label) {
            $field->{'title_'.$code} = strip_tags((string) $label);
            if (array_key_exists($code, (array) ($definition['details'] ?? []))) {
                $field->{'details_'.$code} = $definition['details'][$code];
            } elseif ((int) $definition['type'] === 99) {
                $field->{'details_'.$code} = e((string) ($definition['fixed_texts'][$code] ?? ''));
            } elseif (in_array((int) $definition['type'], ModuleDefinitionCatalog::CHOICE_FIELD_TYPES, true)) {
                $field->{'details_'.$code} = implode("\n", array_map(fn ($option) => $option['labels'][$code], $definition['options'] ?? []));
            }
        }
        $field->type = (int) $definition['type'];
        $field->default_value = filled($definition['default_value'] ?? null) ? (string) $definition['default_value'] : null;
        $field->parent_module_id = $field->type === 18 && filled($definition['parent_module_id'] ?? null)
            ? (int) $definition['parent_module_id']
            : null;
        $field->required = (bool) $definition['required'];
        $field->in_table = (bool) $definition['in_table'];
        $field->in_search = (bool) $definition['in_search'];
        $field->in_listing = (bool) $definition['in_listing'];
        $field->in_page = (bool) $definition['in_page'];
        $field->in_statics = (bool) $definition['in_statics'];
        $field->lang_code = $definition['lang_code'] ?? 'all';
        $field->categories = $definition['categories'] ?? null;
        $field->css_class = $definition['css_class'] ?? null;
        $field->colors = $definition['colors'] ?? null;
        if (in_array($field->type, ModuleDefinitionCatalog::CHOICE_FIELD_TYPES, true) && array_key_exists('options', $definition)) {
            $colors = array_map(fn ($option) => $option['color'] ?? '', $definition['options']);
            // Blank lines deliberately preserve partial-color alignment.
            $field->colors = count(array_filter($colors, fn ($color) => $color !== '')) > 0 ? implode("\n", $colors) : null;
        }
        $field->view_permission_groups = $definition['view_permission_groups'] ?? '0';
        $field->add_permission_groups = $definition['add_permission_groups'] ?? '0';
        $field->edit_permission_groups = $definition['edit_permission_groups'] ?? '0';
        $field->status = 1;
        $field->created_by = $user->id;

        if ($field->type === 99) {
            $field->required = 0;
            $field->in_table = 0;
            $field->in_search = 0;
            $field->in_listing = 0;
            $field->in_statics = 0;
        }
        $field->save();

        if ($field->in_table) {
            $this->appendUserTableColumn($section, $field);
        }

        return $field;
    }

    public function grantSectionAccess(WebmasterSection $section, User $user): void
    {
        $ids = array_unique(array_filter([(int) $user->permissions_id, 1]));
        foreach (Permissions::query()->whereIn('id', $ids)->get() as $permissions) {
            $sections = array_values(array_unique(array_filter(explode(',', (string) $permissions->data_sections))));
            $sections[] = (string) $section->id;
            $permissions->data_sections = implode(',', array_values(array_unique($sections)));
            $permissions->save();
        }
    }

    public function indexAfterCreate(WebmasterSection $section): void
    {
        if (! \Helper::GeneralWebmasterSettings('instant_index')
            || ! \Helper::GeneralWebmasterSettings('instant_index_on_create')
            || ! $section->index_status) {
            return;
        }

        $urls = [];
        foreach (\Helper::languagesList() as $language) {
            $urls[] = \Helper::sectionURL($section->id, $language->code, $section);
        }
        (new GoogleIndexing)->addOrRemove($urls, 0);
    }

    private function attemptIndexAfterCreate(WebmasterSection $section): void
    {
        try {
            $this->indexAfterCreate($section);
        } catch (Throwable $exception) {
            Log::warning('Module instant indexing failed after creation.', [
                'webmaster_section_id' => $section->id,
                'exception' => $exception::class,
            ]);
        }
    }

    private function applyStatuses(WebmasterSection $section, array $definition): void
    {
        if ((int) $definition['module_type'] === 10) {
            foreach (['title_status', 'case_status', 'seo_status', 'no_status', 'photo_status', 'visits_status', 'featured_status', 'index_status', 'tags_status', 'code_status'] as $key) {
                $section->{$key} = 1;
            }
            foreach (['comments_status', 'date_status', 'expire_date_status', 'longtext_status', 'editor_status', 'authors_status', 'attach_file_status', 'extra_attach_file_status', 'multi_images_status', 'maps_status', 'order_status', 'section_icon_status', 'icon_status', 'related_status'] as $key) {
                $section->{$key} = 0;
            }

            return;
        }

        foreach ($definition['basic_fields'] as $column => $enabled) {
            $section->{$column} = $enabled ? 1 : 0;
        }
        foreach ($definition['additional_tabs'] as $column => $enabled) {
            $section->{$column} = $enabled ? 1 : 0;
        }
    }

    private function appendUserTableColumn(WebmasterSection $section, WebmasterSectionField $field): void
    {
        User::query()->whereNotNull('table_columns')->each(function (User $user) use ($section, $field) {
            $columns = json_decode((string) $user->table_columns, true);
            $sectionKey = 'sec_'.$section->id;
            if (! is_array($columns) || ! isset($columns[$sectionKey]) || ! is_array($columns[$sectionKey])) {
                return;
            }
            $columns[$sectionKey]['col_custom_'.$field->id] = 1;
            $user->table_columns = json_encode($columns);
            $user->save();
        });
    }
}

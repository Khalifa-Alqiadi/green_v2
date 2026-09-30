<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModernThemeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $classic = DB::table('settings')->where('theme_name', 'classic')->first();

        if (empty($classic)) {
            return;
        }

        $modern = DB::table('settings')->where('theme_name', 'modern')->first();
        $now = now();

        if (!empty($modern)) {
            DB::table('settings')
                ->where('theme_name', 'modern')
                ->update([
                    'theme_title' => 'Modern Theme',
                    'status' => 1,
                    'updated_at' => $now,
                ]);

            return;
        }

        $excludedColumns = [
            'id',
            'theme_name',
            'theme_title',
            'status',
            'created_at',
            'updated_at',
        ];

        $settings = collect(Schema::getColumnListing('settings'))
            ->reject(fn ($column) => in_array($column, $excludedColumns, true))
            ->mapWithKeys(fn ($column) => [$column => $classic->{$column} ?? null])
            ->all();

        $settings['theme_name'] = 'modern';
        $settings['theme_title'] = 'Modern Theme';
        $settings['status'] = 1;
        $settings['created_at'] = $now;
        $settings['updated_at'] = $now;
        $settings['style_color1'] = "#ba893f";
        $settings['style_color2'] = "#3b3b3a";
        $settings['style_color3'] = "#f3f0ed";
        $settings['style_color4'] = "#eae4df";

        DB::table('settings')->insert($settings);
    }
}

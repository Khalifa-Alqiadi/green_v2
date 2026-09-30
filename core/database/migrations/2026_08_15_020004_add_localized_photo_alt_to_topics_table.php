<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $codes = $this->languageCodes();

        Schema::table('topics', function (Blueprint $table) use ($codes) {
            foreach ($codes as $code) {
                $column = 'photo_alt_'.$code;
                if (!Schema::hasColumn('topics', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            array_map(fn ($code) => 'photo_alt_'.$code, $this->languageCodes()),
            fn ($column) => Schema::hasColumn('topics', $column)
        ));

        if ($columns !== []) {
            Schema::table('topics', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }

    private function languageCodes(): array
    {
        return collect(Schema::getColumnListing('topics'))
            ->filter(fn ($column) => preg_match('/^title_([a-z]{2})$/', $column) === 1)
            ->map(fn ($column) => substr($column, -2))
            ->unique()
            ->values()
            ->all();
    }
};

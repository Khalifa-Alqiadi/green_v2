<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('popups', 'start_date')) {
            Schema::table('popups', function (Blueprint $table) {
                $table->date('start_date')->nullable()->after('show_in');
            });
        }

        if (!Schema::hasColumn('popups', 'end_date')) {
            Schema::table('popups', function (Blueprint $table) {
                $table->date('end_date')->nullable()->after('start_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(['start_date', 'end_date'], fn ($column) => Schema::hasColumn('popups', $column)));

        if (!empty($columns)) {
            Schema::table('popups', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};

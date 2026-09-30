<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->tinyInteger('authors_status')->default(0)->after('tags_status');
        });

        // Preserve the access existing groups had through the modules permission.
        DB::table('permissions')->where('modules_status', 1)->update(['authors_status' => 1]);
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('authors_status');
        });
    }
};

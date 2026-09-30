<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webmaster_sections', function (Blueprint $table) {
            $table->tinyInteger('authors_status')->default(0)->after('tags_status');
        });
    }

    public function down(): void
    {
        Schema::table('webmaster_sections', function (Blueprint $table) {
            $table->dropColumn('authors_status');
        });
    }
};

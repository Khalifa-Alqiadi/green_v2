<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('style_footer_logo')->nullable()->after('style_footer_bg');
        });
        Schema::table('webmaster_settings', function (Blueprint $table) {
            $table->tinyInteger('authors_status')->default(0)->after('menus_status');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('style_footer_logo');
        });
        Schema::table('webmaster_settings', function (Blueprint $table) {
            $table->dropColumn('authors_status');
        });
    }
};

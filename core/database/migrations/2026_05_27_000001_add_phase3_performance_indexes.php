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
        Schema::table('topics', function (Blueprint $table) {
            $table->index(['status', 'webmaster_id', 'featured', 'date', 'id'], 'topics_perf_public_listing');
            $table->index(['status', 'webmaster_id', 'visits'], 'topics_perf_most_viewed');
            $table->index(['webmaster_id', 'status', 'row_no'], 'topics_perf_module_rows');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->index(['webmaster_id', 'father_id', 'status', 'row_no'], 'sections_perf_tree_listing');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->index(['father_id', 'status', 'row_no'], 'menus_perf_tree_listing');
        });

        Schema::table('topic_fields', function (Blueprint $table) {
            $table->index(['topic_id', 'field_id'], 'topic_fields_perf_topic_field');
            $table->index(['field_id', 'topic_id'], 'topic_fields_perf_field_topic');
        });

        Schema::table('topic_categories', function (Blueprint $table) {
            $table->index(['section_id', 'topic_id'], 'topic_categories_perf_section_topic');
            $table->index(['topic_id', 'section_id'], 'topic_categories_perf_topic_section');
        });

        Schema::table('topic_tags', function (Blueprint $table) {
            $table->index(['tag_id', 'topic_id'], 'topic_tags_perf_tag_topic');
            $table->index(['topic_id', 'tag_id'], 'topic_tags_perf_topic_tag');
        });

        Schema::table('topic_blocks', function (Blueprint $table) {
            $table->index(['topic_id', 'status', 'row_no'], 'topic_blocks_perf_topic_status');
        });

        Schema::table('analytics_visitors', function (Blueprint $table) {
            $table->index(['ip', 'date'], 'analytics_visitors_perf_ip_date');
        });

        Schema::table('analytics_pages', function (Blueprint $table) {
            $table->index(['visitor_id', 'ip', 'date'], 'analytics_pages_perf_visitor_ip_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_pages', function (Blueprint $table) {
            $table->dropIndex('analytics_pages_perf_visitor_ip_date');
        });

        Schema::table('analytics_visitors', function (Blueprint $table) {
            $table->dropIndex('analytics_visitors_perf_ip_date');
        });

        Schema::table('topic_blocks', function (Blueprint $table) {
            $table->dropIndex('topic_blocks_perf_topic_status');
        });

        Schema::table('topic_tags', function (Blueprint $table) {
            $table->dropIndex('topic_tags_perf_tag_topic');
            $table->dropIndex('topic_tags_perf_topic_tag');
        });

        Schema::table('topic_categories', function (Blueprint $table) {
            $table->dropIndex('topic_categories_perf_section_topic');
            $table->dropIndex('topic_categories_perf_topic_section');
        });

        Schema::table('topic_fields', function (Blueprint $table) {
            $table->dropIndex('topic_fields_perf_topic_field');
            $table->dropIndex('topic_fields_perf_field_topic');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex('menus_perf_tree_listing');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex('sections_perf_tree_listing');
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('topics_perf_public_listing');
            $table->dropIndex('topics_perf_most_viewed');
            $table->dropIndex('topics_perf_module_rows');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('tokenable_type');
                $table->unsignedBigInteger('tokenable_id');
                $table->text('name');
                $table->string('token', 64);
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['tokenable_type', 'tokenable_id'], 'pat_tokenable_idx');
                $table->unique('token', 'pat_token_unique');
                $table->index('expires_at', 'pat_expires_idx');
            });

            return;
        }

        // A failed MySQL migration may leave the table behind before its indexes
        // are added. Complete that partial table safely instead of dropping it.
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'tokenable_type', function (Blueprint $table) {
            $table->string('tokenable_type');
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'tokenable_id', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id');
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'name', function (Blueprint $table) {
            $table->text('name');
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'token', function (Blueprint $table) {
            $table->string('token', 64);
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'abilities', function (Blueprint $table) {
            $table->text('abilities')->nullable();
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'last_used_at', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable();
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'expires_at', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable();
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'created_at', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable();
        });
        Schema::whenTableDoesntHaveColumn('personal_access_tokens', 'updated_at', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });

        Schema::whenTableDoesntHaveIndex(
            'personal_access_tokens',
            ['tokenable_type', 'tokenable_id'],
            fn (Blueprint $table) => $table->index(['tokenable_type', 'tokenable_id'], 'pat_tokenable_idx')
        );
        Schema::whenTableDoesntHaveIndex(
            'personal_access_tokens',
            ['token'],
            fn (Blueprint $table) => $table->unique('token', 'pat_token_unique'),
            'unique'
        );
        Schema::whenTableDoesntHaveIndex(
            'personal_access_tokens',
            ['expires_at'],
            fn (Blueprint $table) => $table->index('expires_at', 'pat_expires_idx')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};

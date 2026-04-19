<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLottoNavbarsAndItems extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_navbars')) {
            Schema::create('lotto_navbars', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('code', 64);
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_published')->default(false);
                $table->unsignedInteger('published_version')->nullable();
                $table->string('published_active_code', 64)
                    ->nullable()
                    ->storedAs('case when is_published = 1 and is_active = 1 then code else null end');
                $table->dateTime('published_at')->nullable();
                $table->timestamps();

                $table->index(['code'], 'lotto_navbars_code_idx');
                $table->index(['code', 'is_active', 'is_published'], 'lotto_navbars_code_active_published_idx');
                $table->unique(['code', 'published_version'], 'lotto_navbars_code_published_version_uniq');
                $table->unique(['published_active_code'], 'lotto_navbars_published_active_code_uniq');
            });
        }

        if (! Schema::hasTable('lotto_navbar_items')) {
            Schema::create('lotto_navbar_items', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('navbar_id');
                $table->string('key', 64);
                $table->string('item_type', 32)->default('normal');
                $table->string('icon_type', 32)->default('preset');
                $table->string('icon')->nullable();
                $table->json('label_json')->nullable();
                $table->string('action_type', 32)->default('route');
                $table->string('action_value', 255)->nullable();
                $table->unsignedInteger('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('active_sort_order')
                    ->nullable()
                    ->storedAs('case when is_active = 1 then sort_order else null end');
                $table->timestamps();

                $table->unique(['navbar_id', 'key'], 'lotto_navbar_items_navbar_key_uniq');
                $table->unique(['navbar_id', 'active_sort_order'], 'lotto_navbar_items_navbar_active_sort_uniq');
                $table->index(['navbar_id', 'sort_order'], 'lotto_navbar_items_navbar_sort_idx');
                $table->foreign('navbar_id')
                    ->references('id')
                    ->on('lotto_navbars')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lotto_navbar_items')) {
            Schema::dropIfExists('lotto_navbar_items');
        }

        if (Schema::hasTable('lotto_navbars')) {
            Schema::dropIfExists('lotto_navbars');
        }
    }
}

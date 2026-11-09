<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createCategories();
        $this->createDocuments();
        $this->createImages();
    }

    public function down(): void
    {
        Schema::dropIfExists('faq__document_images');
        Schema::dropIfExists('faq__images');
        Schema::dropIfExists('faq__document_revisions');
        Schema::dropIfExists('faq__documents');
        Schema::dropIfExists('faq__categories');
    }

    private function createCategories(): void
    {
        Schema::create('faq__categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('faq__categories')->nullOnDelete();
            $table->string('locale', 12);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['locale', 'parent_id', 'name']);
        });
    }

    private function createDocuments(): void
    {
        Schema::create('faq__documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('faq__categories')->nullOnDelete();
            $table->uuid('translation_group')->nullable();
            $table->string('locale', 12);
            $table->string('slug', 180);
            $table->string('title', 180);
            $table->longText('body');
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->boolean('noindex')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('permission', 100)->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'published', 'position']);
            $table->index(['deleted_at', 'id'], 'faq_documents_trash_index');
            $table->unique(['translation_group', 'locale'], 'faq_translation_locale_unique');
            $table->index(['published', 'deleted_at', 'updated_at'], 'faq_documents_retention_index');
        });
        Schema::create('faq__document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('faq__documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('snapshot');
            $table->timestamp('created_at');
            $table->index(['document_id', 'created_at', 'id'], 'faq_revisions_retention_index');
        });
    }

    private function createImages(): void
    {
        Schema::create('faq__images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path', 255);
            $table->string('hash', 64);
            $table->string('original_name', 255);
            $table->string('mime', 64);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->timestamps();
            $table->index(['uploader_id', 'hash']);
            $table->index('updated_at');
        });
        Schema::create('faq__document_images', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained('faq__documents')->cascadeOnDelete();
            $table->foreignUuid('image_id')->constrained('faq__images')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->primary(['resource_id', 'image_id']);
            $table->index(['image_id', 'active']);
        });
    }
};

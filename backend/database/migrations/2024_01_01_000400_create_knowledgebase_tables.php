<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['project', 'personal', 'knowledge']);
            $table->enum('visibility', ['private', 'public', 'team'])->default('team');
            $table->string('avatar')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        Schema::create('space_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'editor', 'viewer']);
            $table->timestamps();
            $table->unique(['space_id', 'user_id']);
        });

        Schema::create('space_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->enum('target_type', ['role', 'group', 'user']);
            $table->string('target_value');
            $table->string('permission');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('version')->default(1);
            $table->json('labels')->default('[]');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'status']);
            $table->index(['parent_id']);
        });

        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->integer('version');
            $table->foreignId('author_id')->constrained('users');
            $table->string('summary')->nullable();
            $table->longText('body_snapshot');
            $table->timestamps();

            $table->unique(['page_id', 'version']);
        });

        Schema::create('page_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('body');
            $table->integer('line_number')->nullable();
            $table->integer('column_number')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });

        Schema::create('page_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('page_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->timestamps();

            $table->unique(['page_id', 'label']);
        });

        Schema::create('issue_knowledgebase_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('context')->default('embedded');
            $table->timestamps();

            $table->unique(['issue_id', 'page_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_knowledgebase_links');
        Schema::dropIfExists('page_labels');
        Schema::dropIfExists('page_attachments');
        Schema::dropIfExists('page_comments');
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('space_permissions');
        Schema::dropIfExists('space_members');
        Schema::dropIfExists('spaces');
    }
};

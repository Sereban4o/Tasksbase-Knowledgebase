<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_epic')->default(false);
            $table->boolean('is_subtask')->default(false);
            $table->timestamps();
        });

        Schema::create('issue_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('todo');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_type_id')->constrained();
            $table->foreignId('status_id')->constrained('issue_statuses');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->foreignId('epic_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->string('summary');
            $table->longText('description')->nullable();
            $table->string('key')->unique();
            $table->integer('sequence')->default(0);
            $table->json('custom_fields')->nullable();
            $table->enum('priority', ['lowest', 'low', 'medium', 'high', 'highest'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('estimated_start')->nullable();
            $table->date('estimated_end')->nullable();
            $table->integer('story_points')->nullable();
            $table->decimal('original_estimate_hours', 10, 2)->nullable();
            $table->decimal('time_spent_hours', 10, 2)->default(0);
            $table->decimal('remaining_hours', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status_id']);
            $table->index(['project_id', 'issue_type_id']);
            $table->index(['assigned_to']);
            $table->index(['epic_id']);
            $table->index(['parent_id']);
        });

        Schema::create('issue_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_comment_id')->nullable()->constrained('issue_comments')->nullOnDelete();
            $table->longText('body');
            $table->json('rendered_body')->nullable();
            $table->decimal('time_spent_hours', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('issue_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('issue_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('target_issue_id')->constrained('issues')->cascadeOnDelete();
            $table->string('link_type');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['source_issue_id', 'target_issue_id', 'link_type']);
        });

        Schema::create('issue_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('config')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('issue_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_field_id')->constrained('issue_custom_fields')->cascadeOnDelete();
            $table->json('value');
            $table->unique(['issue_id', 'custom_field_id']);
        });

        Schema::create('issue_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_status_id')->constrained('issue_statuses');
            $table->foreignId('to_status_id')->constrained('issue_statuses');
            $table->string('name');
            $table->json('conditions')->nullable();
            $table->json('fields_required')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'from_status_id', 'to_status_id']);
        });

        Schema::create('issue_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_workflows');
        Schema::dropIfExists('issue_transitions');
        Schema::dropIfExists('issue_custom_field_values');
        Schema::dropIfExists('issue_custom_fields');
        Schema::dropIfExists('issue_links');
        Schema::dropIfExists('issue_attachments');
        Schema::dropIfExists('issue_comments');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('issue_statuses');
        Schema::dropIfExists('issue_types');
    }
};

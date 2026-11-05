<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('type', ['scrum', 'kanban', 'basic']);
            $table->enum('visibility', ['private', 'public', 'team'])->default('team');
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'member', 'viewer']);
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('project_role_members', function (Blueprint $table) {
            $table->foreignId('project_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_role_members');
        Schema::dropIfExists('project_roles');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
    }
};

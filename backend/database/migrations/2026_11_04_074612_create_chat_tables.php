<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chats table
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('name')->nullable(); // null for DM
            $table->enum('type', ['dm', 'group', 'channel'])->default('dm');
            $table->string('avatar')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Chat members
        Schema::create('chat_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'member', 'muted'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['chat_id', 'user_id']);
        });

        // Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->text('body')->nullable();
            $table->enum('type', ['text', 'image', 'file', 'system'])->default('text');
            $table->json('metadata')->nullable(); // {emoji, reply_to, etc}
            $table->integer('edit_count')->default(0);
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        // Message files
        Schema::create('message_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->string('mime_type');
            $table->integer('size');
            $table->string('thumbnail_url')->nullable();
            $table->timestamps();
        });

        // Message reactions
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji');
            $table->timestamps();
            $table->unique(['message_id', 'user_id', 'emoji']);
        });

        // Online users
        Schema::create('online_users', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('socket_id');
            $table->timestamp('last_seen')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_users');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('message_files');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chat_members');
        Schema::dropIfExists('chats');
    }
};

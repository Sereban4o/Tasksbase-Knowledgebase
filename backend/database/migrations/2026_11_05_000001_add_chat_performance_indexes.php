<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index('chat_id');
            $table->index('sender_id');
            $table->index(['chat_id', 'created_at']);
            $table->index('deleted_at');
        });

        Schema::table('chat_members', function (Blueprint $table) {
            $table->index('last_read_at');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['chat_id']);
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['chat_id', 'created_at']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('chat_members', function (Blueprint $table) {
            $table->dropIndex(['last_read_at']);
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex(['last_message_at']);
        });
    }
};

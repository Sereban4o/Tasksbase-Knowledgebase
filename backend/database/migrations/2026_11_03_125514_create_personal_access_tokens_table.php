<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table already exists from sanctum migration
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};

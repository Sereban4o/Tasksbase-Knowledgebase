<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('integration')->default('one_c');
            $table->string('entity_type');       // user, issue, project, page, ...
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('external_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['integration', 'entity_type', 'external_id']);
            $table->index(['integration', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_mappings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('issue_statuses', 'sort_order')) {
            Schema::table('issue_statuses', function (Blueprint $table) {
                $table->integer('sort_order')->default(0);
            });
        }

        // Backfill existing statuses so the initial order follows their id.
        DB::table('issue_statuses')->update(['sort_order' => DB::raw('id')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('issue_statuses', 'sort_order')) {
            Schema::table('issue_statuses', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};

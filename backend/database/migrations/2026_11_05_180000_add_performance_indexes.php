<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Issues are frequently filtered/sorted by project and updated_at
        // (board/report views, 1C changedSince()).
        if (!Schema::hasIndex('issues', 'issues_project_updated_idx')) {
            Schema::table('issues', function (Blueprint $table) {
                $table->index(['project_id', 'updated_at'], 'issues_project_updated_idx');
            });
        }

        // Comments are loaded per issue.
        if (!Schema::hasIndex('issue_comments', 'issue_comments_issue_idx')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->index('issue_id', 'issue_comments_issue_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('issues', 'issues_project_updated_idx')) {
            Schema::table('issues', function (Blueprint $table) {
                $table->dropIndex('issues_project_updated_idx');
            });
        }

        if (Schema::hasIndex('issue_comments', 'issue_comments_issue_idx')) {
            Schema::table('issue_comments', function (Blueprint $table) {
                $table->dropIndex('issue_comments_issue_idx');
            });
        }
    }
};

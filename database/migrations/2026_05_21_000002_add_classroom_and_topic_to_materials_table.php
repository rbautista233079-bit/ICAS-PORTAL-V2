<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('materials', 'classroom_id') || ! Schema::hasColumn('materials', 'topic_id')) {
            Schema::table('materials', function (Blueprint $table) {
                if (! Schema::hasColumn('materials', 'classroom_id')) {
                    $table->foreignId('classroom_id')->nullable()->after('id')->constrained('classrooms')->nullOnDelete();
                }
                if (! Schema::hasColumn('materials', 'topic_id')) {
                    $table->foreignId('topic_id')->nullable()->after('classroom_id')->constrained('topics')->nullOnDelete();
                }
                $table->index(['classroom_id', 'topic_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'classroom_id')) {
                $table->dropForeign(['classroom_id']);
            }
            if (Schema::hasColumn('materials', 'topic_id')) {
                $table->dropForeign(['topic_id']);
            }
            $table->dropIndex(['classroom_id', 'topic_id']);
            $table->dropColumn(['classroom_id', 'topic_id']);
        });
    }
};

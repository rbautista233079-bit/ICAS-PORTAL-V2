<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_image_blob')) {
                $table->dropColumn('profile_image_blob');
            }
            if (Schema::hasColumn('users', 'receipt_proof_blob')) {
                $table->dropColumn('receipt_proof_blob');
            }
            if (Schema::hasColumn('users', 'student_id_proof_blob')) {
                $table->dropColumn('student_id_proof_blob');
            }
        });

        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'attachment_blob')) {
                $table->dropColumn('attachment_blob');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'profile_image_blob')) {
            DB::statement('ALTER TABLE users ADD profile_image_blob LONGBLOB NULL');
        }
        if (! Schema::hasColumn('users', 'receipt_proof_blob')) {
            DB::statement('ALTER TABLE users ADD receipt_proof_blob LONGBLOB NULL');
        }
        if (! Schema::hasColumn('users', 'student_id_proof_blob')) {
            DB::statement('ALTER TABLE users ADD student_id_proof_blob LONGBLOB NULL');
        }

        if (! Schema::hasColumn('announcements', 'attachment_blob')) {
            DB::statement('ALTER TABLE announcements ADD attachment_blob LONGBLOB NULL');
        }
    }
};

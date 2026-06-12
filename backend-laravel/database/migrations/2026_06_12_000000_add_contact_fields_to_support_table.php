<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support', function (Blueprint $table) {
            if (!Schema::hasColumn('support', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (!Schema::hasColumn('support', 'subject')) {
                $table->string('subject')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('support', 'message')) {
                $table->text('message')->nullable()->after('subject');
            }
        });

        DB::statement("ALTER TABLE support MODIFY type ENUM('password_recovery', 'contact') DEFAULT 'password_recovery'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE support MODIFY type ENUM('password_recovery') DEFAULT 'password_recovery'");

        Schema::table('support', function (Blueprint $table) {
            $table->dropColumn(['phone', 'subject', 'message']);
        });
    }
};

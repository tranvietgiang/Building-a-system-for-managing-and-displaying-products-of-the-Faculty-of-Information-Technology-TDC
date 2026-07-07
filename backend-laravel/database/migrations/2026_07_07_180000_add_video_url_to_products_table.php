<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'video_url')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('video_url', 1000)->nullable()->after('demo_link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'video_url')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('video_url');
            });
        }
    }
};

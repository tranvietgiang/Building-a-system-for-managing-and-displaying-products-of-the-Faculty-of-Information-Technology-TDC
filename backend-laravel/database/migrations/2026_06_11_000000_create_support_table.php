<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support', function (Blueprint $table) {
            $table->id('support_id');
            $table->string('identifier');
            $table->string('user_id', 15)->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->enum('type', ['password_recovery', 'contact'])->default('password_recovery');
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->string('processed_by', 15)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('processed_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support');
    }
};

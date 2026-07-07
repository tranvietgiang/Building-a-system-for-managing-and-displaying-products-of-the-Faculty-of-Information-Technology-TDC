<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbox_training_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 15)->nullable();
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->foreignId('major_id')->nullable()
                ->constrained('majors', 'major_id')
                ->nullOnDelete();
            $table->string('role', 30)->nullable();
            $table->text('message');
            $table->string('normalized_message', 500)->nullable();
            $table->json('analysis')->nullable();
            $table->string('source', 80)->nullable();
            $table->longText('reply')->nullable();
            $table->json('products')->nullable();
            $table->unsignedSmallInteger('products_count')->default(0);
            $table->boolean('needs_training')->default(false);
            $table->boolean('reviewed')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['needs_training', 'reviewed', 'created_at'], 'chatbox_training_status_idx');
            $table->index(['role', 'created_at'], 'chatbox_training_role_idx');
            $table->index(['source', 'created_at'], 'chatbox_training_source_idx');
            $table->index(['major_id', 'created_at'], 'chatbox_training_major_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbox_training_logs');
    }
};

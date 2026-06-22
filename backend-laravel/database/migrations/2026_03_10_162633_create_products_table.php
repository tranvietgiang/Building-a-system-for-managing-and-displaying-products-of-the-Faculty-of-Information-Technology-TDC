<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id('product_id');

            $table->string('title');

            $table->text('description')->nullable();

            $table->json('team_members')->nullable();

            $table->string('thumbnail');

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->string("user_id", 15);

            $table->foreign("user_id")
                ->references("user_id")
                ->on("users")
                ->cascadeOnDelete();

            $table->foreignId("major_id")->constrained("majors", "major_id")->onDelete("cascade");

            $table->foreignId("cate_id")->constrained("categories", "cate_id")->onDelete("cascade");

            $table->string("approved_by", 15)->nullable();

            $table->string("advisor_name", 100)->nullable();

            $table->string("awards", 255)->nullable();

            $table->string("github_link", 500)->nullable();

            $table->string("demo_link", 500)->nullable();

            $table->foreign("approved_by")
                ->references("user_id")
                ->on("users")
                ->nullOnDelete();

            //ngày sinh viên chính thức nộp / đăng sản phẩm
            $table->date('submitted_at')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

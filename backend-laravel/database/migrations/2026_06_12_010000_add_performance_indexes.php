<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'major_id', 'approved_at'], 'products_status_major_approved_at_idx');
            $table->index(['status', 'submitted_at'], 'products_status_submitted_at_idx');
            $table->index(['user_id', 'status'], 'products_user_status_idx');
            $table->index(['approved_by', 'status'], 'products_approved_by_status_idx');
            $table->index(['cate_id', 'status'], 'products_cate_status_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'major_id'], 'users_role_major_idx');
            $table->index(['major_id', 'role'], 'users_major_role_idx');
        });

        Schema::table('support', function (Blueprint $table) {
            $table->index(['type', 'status', 'created_at'], 'support_type_status_created_at_idx');
            $table->index(['identifier', 'status'], 'support_identifier_status_idx');
            $table->index(['email', 'status'], 'support_email_status_idx');
        });

        Schema::table('product_files', function (Blueprint $table) {
            $table->index(['product_id', 'file_type'], 'product_files_product_file_type_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['teacher_id', 'product_id'], 'reviews_teacher_product_idx');
        });

        Schema::table('product_tags', function (Blueprint $table) {
            $table->index(['tag_name', 'product_id'], 'product_tags_tag_product_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_tags', function (Blueprint $table) {
            $table->dropIndex('product_tags_tag_product_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_teacher_product_idx');
        });

        Schema::table('product_files', function (Blueprint $table) {
            $table->dropIndex('product_files_product_file_type_idx');
        });

        Schema::table('support', function (Blueprint $table) {
            $table->dropIndex('support_type_status_created_at_idx');
            $table->dropIndex('support_identifier_status_idx');
            $table->dropIndex('support_email_status_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_major_idx');
            $table->dropIndex('users_major_role_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_major_approved_at_idx');
            $table->dropIndex('products_status_submitted_at_idx');
            $table->dropIndex('products_user_status_idx');
            $table->dropIndex('products_approved_by_status_idx');
            $table->dropIndex('products_cate_status_idx');
        });
    }
};

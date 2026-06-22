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
        Schema::create('product_graphic', function (Blueprint $table) {
            $table->id('product_graphic_id');

            $table->foreignId('product_id')
                ->constrained('products', 'product_id')
                ->cascadeOnDelete();

            $table->string('design_type', 50);
            $table->text('tools_used')->nullable();
            $table->json('color_palette')->nullable();

            $table->string('behance_link', 255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_graphic');
    }
};

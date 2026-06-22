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
        Schema::create('product_mmt', function (Blueprint $table) {
            $table->id('product_mmt_id');

            $table->foreignId('product_id')
                ->constrained('products', 'product_id')
                ->cascadeOnDelete();

            // đúng theo UI đang hiển thị
            $table->text('network_protocol')->nullable();  // SMTP, OSPF...
            $table->string('topology_type', 50);      // Hybrid, Star...
            $table->string('simulation_tool', 100);   // Nmap, GNS3...

            $table->string('config_file')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_mmt');
    }
};

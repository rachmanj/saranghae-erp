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
        Schema::create('inventory_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->string('warehouse'); // Store as a string instead of foreign key
            $table->integer('stock_quantity')->default(0);
            $table->decimal('stock_value', 15, 2)->nullable(); // Value in IDR
            $table->string('location_in_warehouse')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            
            // Create a unique constraint on inventory_id and warehouse
            $table->unique(['inventory_id', 'warehouse']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_warehouse');
    }
};

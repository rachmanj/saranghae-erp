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
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('partner_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
    }
}; 
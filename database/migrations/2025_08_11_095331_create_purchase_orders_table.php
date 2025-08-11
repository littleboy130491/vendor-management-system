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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('issued_by')->constrained('users');
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['draft', 'approved', 'sent', 'acknowledged', 'delivered', 'completed', 'cancelled'])->default('draft');
            $table->date('issued_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('delivery_address')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'issued_date']);
            $table->index(['vendor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};

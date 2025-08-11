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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 15, 2);
            $table->enum('method', ['bank_transfer', 'cheque', 'card', 'ach', 'wire'])->default('bank_transfer');
            $table->date('paid_date');
            $table->foreignId('processed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->json('bank_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

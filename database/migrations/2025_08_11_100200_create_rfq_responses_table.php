<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rfq_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->decimal('quoted_amount', 12, 2);
            $table->unsignedInteger('delivery_time_days')->nullable();
            $table->enum('status', ['submitted', 'accepted', 'rejected', 'withdrawn'])->default('submitted');
            $table->float('technical_score')->nullable();
            $table->float('commercial_score')->nullable();
            $table->float('total_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'vendor_id']);
            $table->index(['rfq_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_responses');
    }
};
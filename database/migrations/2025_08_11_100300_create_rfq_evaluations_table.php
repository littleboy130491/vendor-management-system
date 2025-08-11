<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rfq_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_response_id')->constrained()->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade');
            $table->json('criteria_scores')->nullable();
            $table->text('comments')->nullable();
            $table->float('total_score')->nullable();
            $table->timestamps();

            $table->unique(['rfq_response_id', 'evaluator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_evaluations');
    }
};
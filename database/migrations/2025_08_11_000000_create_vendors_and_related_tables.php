<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'is_featured']);
            $table->index('sort_order');
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained('vendor_categories');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id')->unique()->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'blacklisted'])->default('pending');
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('rating_average');
        });

        Schema::create('vendor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->tinyInteger('rating_quality')->unsigned();
            $table->tinyInteger('rating_timeliness')->unsigned();
            $table->tinyInteger('rating_communication')->unsigned();
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('issued_by')->constrained('users');
            $table->string('type');
            $table->text('details');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_warnings');
        Schema::dropIfExists('vendor_reviews');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('vendor_categories');
    }
};
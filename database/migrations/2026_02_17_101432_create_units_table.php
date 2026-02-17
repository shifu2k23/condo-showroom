<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name')->index();
            $table->string('slug')->nullable()->unique();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['AVAILABLE', 'UNAVAILABLE'])->default('AVAILABLE')->index();
            $table->integer('nightly_price_php')->nullable();
            $table->integer('monthly_price_php')->nullable();
            $table->enum('price_display_mode', ['NIGHT', 'MONTH'])->default('NIGHT');
            $table->enum('estimator_mode', ['HYBRID', 'NIGHTLY_ONLY', 'MONTHLY_ONLY'])->default('HYBRID');
            $table->boolean('allow_estimator')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viewing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('requester_email')->nullable();
            $table->string('requester_phone')->nullable();
            $table->dateTime('requested_start_at')->index();
            $table->dateTime('requested_end_at')->nullable();
            $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED'])->default('PENDING')->index();
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viewing_requests');
    }
};

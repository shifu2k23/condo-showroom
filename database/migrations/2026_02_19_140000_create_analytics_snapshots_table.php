<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('period_type', 16);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metrics');
            $table->timestamps();

            $table->unique(['period_type', 'period_start', 'period_end'], 'analytics_snapshots_period_unique');
            $table->index(['period_type', 'period_start'], 'analytics_snapshots_type_start_idx');
            $table->index(['period_type', 'created_at'], 'analytics_snapshots_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};


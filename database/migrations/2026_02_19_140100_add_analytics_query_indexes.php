<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viewing_requests', function (Blueprint $table): void {
            $table->index(['requested_start_at', 'status'], 'viewing_requests_window_status_idx');
        });

        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'maintenance_tickets_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->dropIndex('maintenance_tickets_status_created_idx');
        });

        Schema::table('viewing_requests', function (Blueprint $table): void {
            $table->dropIndex('viewing_requests_window_status_idx');
        });
    }
};


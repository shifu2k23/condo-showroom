<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->index(
                ['unit_id', 'status', 'starts_at', 'ends_at'],
                'rentals_unit_status_window_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropIndex('rentals_unit_status_window_idx');
        });
    }
};

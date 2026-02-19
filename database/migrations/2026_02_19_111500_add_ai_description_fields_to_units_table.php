<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->longText('ai_description_draft')->nullable()->after('description');
            $table->json('ai_description_meta')->nullable()->after('ai_description_draft');
            $table->timestamp('ai_description_generated_at')->nullable()->after('ai_description_meta');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_description_draft',
                'ai_description_meta',
                'ai_description_generated_at',
            ]);
        });
    }
};

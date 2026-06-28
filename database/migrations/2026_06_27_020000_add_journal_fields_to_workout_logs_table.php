<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('energy_score')->nullable()->after('exertion_rating');
            $table->unsignedTinyInteger('soreness_score')->nullable()->after('energy_score');
            $table->unsignedTinyInteger('stress_score')->nullable()->after('soreness_score');
            $table->unsignedTinyInteger('sleep_quality_score')->nullable()->after('stress_score');
        });
    }

    public function down(): void
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            $table->dropColumn([
                'energy_score',
                'soreness_score',
                'stress_score',
                'sleep_quality_score',
            ]);
        });
    }
};

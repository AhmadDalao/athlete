<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metric_snapshots', function (Blueprint $table) {
            $table->unsignedInteger('sleep_need_minutes')->nullable()->after('sleep_minutes');
            $table->decimal('sleep_performance_percentage', 5, 2)->nullable()->after('sleep_need_minutes');
            $table->decimal('sleep_consistency_percentage', 5, 2)->nullable()->after('sleep_performance_percentage');
            $table->decimal('sleep_efficiency_percentage', 5, 2)->nullable()->after('sleep_consistency_percentage');
            $table->unsignedSmallInteger('rem_sleep_minutes')->nullable()->after('sleep_efficiency_percentage');
            $table->unsignedSmallInteger('slow_wave_sleep_minutes')->nullable()->after('rem_sleep_minutes');
            $table->decimal('respiratory_rate', 6, 2)->nullable()->after('heart_rate_variability');
            $table->decimal('blood_oxygen_percent', 5, 2)->nullable()->after('respiratory_rate');
            $table->decimal('skin_temperature_celsius', 5, 2)->nullable()->after('blood_oxygen_percent');
        });
    }

    public function down(): void
    {
        Schema::table('metric_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'sleep_need_minutes',
                'sleep_performance_percentage',
                'sleep_consistency_percentage',
                'sleep_efficiency_percentage',
                'rem_sleep_minutes',
                'slow_wave_sleep_minutes',
                'respiratory_rate',
                'blood_oxygen_percent',
                'skin_temperature_celsius',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_metric_ingests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_connection_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->string('external_event_id')->nullable();
            $table->json('payload');
            $table->string('processing_status')->default('processed');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['device_connection_id', 'metric_date'], 'dmi_connection_metric_date_idx');
            $table->unique(['device_connection_id', 'external_event_id'], 'dmi_connection_external_event_uidx');
        });

        Schema::create('metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_connection_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->date('metric_date');
            $table->decimal('readiness_score', 5, 2)->nullable();
            $table->decimal('strain_score', 6, 2)->nullable();
            $table->unsignedInteger('sleep_minutes')->nullable();
            $table->unsignedInteger('steps')->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('calories_burned')->nullable();
            $table->unsignedSmallInteger('active_minutes')->nullable();
            $table->unsignedSmallInteger('resting_heart_rate')->nullable();
            $table->decimal('heart_rate_variability', 8, 2)->nullable();
            $table->decimal('training_load', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['device_connection_id', 'metric_date'], 'metric_snapshots_connection_date_uidx');
            $table->index(['user_id', 'metric_date'], 'metric_snapshots_user_date_idx');
            $table->index(['provider', 'metric_date'], 'metric_snapshots_provider_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_snapshots');
        Schema::dropIfExists('device_metric_ingests');
    }
};

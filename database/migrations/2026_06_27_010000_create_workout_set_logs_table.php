<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_set_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('exercise_index');
            $table->string('exercise_name');
            $table->unsignedSmallInteger('set_number');
            $table->string('target_reps')->nullable();
            $table->string('target_load')->nullable();
            $table->unsignedSmallInteger('target_rest_seconds')->nullable();
            $table->string('actual_reps')->nullable();
            $table->string('actual_load')->nullable();
            $table->unsignedTinyInteger('actual_rpe')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['training_session_id', 'athlete_id', 'exercise_index', 'set_number'], 'workout_set_unique');
            $table->index(['workout_log_id', 'exercise_index']);
            $table->index(['athlete_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_set_logs');
    }
};

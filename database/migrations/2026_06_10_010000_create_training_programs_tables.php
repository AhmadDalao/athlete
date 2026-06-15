<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('active');
            $table->string('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'status']);
            $table->index(['athlete_id', 'status']);
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('scheduled_date');
            $table->string('focus')->nullable();
            $table->text('instructions')->nullable();
            $table->json('exercises')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['training_program_id', 'scheduled_date']);
        });

        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('completion_status');
            $table->timestamp('performed_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('exertion_rating')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['training_session_id', 'athlete_id']);
            $table->index(['athlete_id', 'completion_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_programs');
    }
};

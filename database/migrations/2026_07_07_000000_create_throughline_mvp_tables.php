<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->index();
            $table->string('status')->default('active')->index();
            $table->string('primary_goal')->nullable();
            $table->text('bio')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('user_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission');
            $table->unique(['user_id', 'permission']);
        });

        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general')->index();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('entity')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('summary');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('recipient');
            $table->string('subject');
            $table->string('type')->index();
            $table->string('status')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });

        Schema::create('athlete_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('token')->unique();
            $table->string('status')->default('pending')->index();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('coach_athlete_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active')->index();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();
            $table->unique(['coach_id', 'athlete_id']);
        });

        Schema::create('training_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('goal')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('training_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('focus')->nullable();
            $table->date('scheduled_on')->index();
            $table->string('status')->default('scheduled')->index();
            $table->json('exercises')->nullable();
            $table->text('coach_notes')->nullable();
            $table->string('media_url')->nullable();
            $table->timestamps();
        });

        Schema::create('workout_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('partial')->index();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('rpe')->nullable();
            $table->json('set_logs')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['training_session_id', 'athlete_id']);
        });

        Schema::create('progress_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->date('logged_on')->index();
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedInteger('calories')->nullable();
            $table->unsignedInteger('protein')->nullable();
            $table->unsignedInteger('hydration')->nullable();
            $table->unsignedTinyInteger('sleep_quality')->nullable();
            $table->unsignedTinyInteger('soreness')->nullable();
            $table->unsignedTinyInteger('energy')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['athlete_id', 'logged_on']);
        });
    }

    public function down(): void
    {
        collect([
            'progress_entries',
            'workout_logs',
            'training_sessions',
            'training_programs',
            'coach_athlete_assignments',
            'athlete_invitations',
            'contact_submissions',
            'email_logs',
            'audit_logs',
            'platform_settings',
            'user_permissions',
            'cache',
            'sessions',
            'password_reset_tokens',
            'users',
        ])->each(fn (string $table) => Schema::dropIfExists($table));
    }
};

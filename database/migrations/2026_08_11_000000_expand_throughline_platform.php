<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users', indexName: 'org_owner_fk')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->string('timezone')->default('Asia/Riyadh');
            $table->string('default_theme')->default('system');
            $table->string('plan_key')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('current_organization_id')->nullable()->after('id')->constrained('organizations', indexName: 'users_current_org_fk')->nullOnDelete();
            $table->string('theme_preference')->default('system')->after('status');
            $table->string('avatar_path')->nullable()->after('bio');
        });

        Schema::create('organization_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'org_memberships_org_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(indexName: 'org_memberships_user_fk')->cascadeOnDelete();
            $table->string('role')->index();
            $table->string('status')->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id'], 'org_memberships_org_user_uq');
        });

        Schema::create('membership_permission_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_membership_id')->constrained(indexName: 'permission_overrides_membership_fk')->cascadeOnDelete();
            $table->string('permission');
            $table->boolean('allowed')->default(true);
            $table->timestamps();
            $table->unique(['organization_membership_id', 'permission'], 'permission_overrides_member_perm_uq');
        });

        Schema::create('organization_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'org_settings_org_fk')->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
            $table->unique(['organization_id', 'key'], 'org_settings_org_key_uq');
        });

        Schema::create('athlete_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'athlete_profiles_org_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(indexName: 'athlete_profiles_user_fk')->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->decimal('height_cm', 6, 2)->nullable();
            $table->string('sport')->nullable();
            $table->string('position')->nullable();
            $table->string('timezone')->default('Asia/Riyadh');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id'], 'athlete_profiles_org_user_uq');
        });

        Schema::create('coach_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'coach_profiles_org_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(indexName: 'coach_profiles_user_fk')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->json('specialties')->nullable();
            $table->text('certifications')->nullable();
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id'], 'coach_profiles_org_user_uq');
        });

        foreach (['athlete_invitations', 'coach_athlete_assignments', 'training_programs', 'training_sessions', 'workout_logs', 'progress_entries', 'audit_logs', 'email_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->foreignId('organization_id')->nullable()->constrained(indexName: $tableName.'_org_fk')->nullOnDelete();
            });
        }

        Schema::table('coach_athlete_assignments', function (Blueprint $table): void {
            $table->dropUnique(['coach_id', 'athlete_id']);
            $table->unique(['organization_id', 'coach_id', 'athlete_id'], 'coach_athlete_assignments_org_pair_unique');
        });

        Schema::table('progress_entries', function (Blueprint $table): void {
            $table->dropUnique(['athlete_id', 'logged_on']);
            $table->unique(['organization_id', 'athlete_id', 'logged_on'], 'progress_entries_org_athlete_day_unique');
        });

        Schema::table('training_programs', function (Blueprint $table): void {
            $table->unsignedBigInteger('athlete_id')->nullable()->change();
            $table->boolean('is_template')->default(false)->index();
            $table->string('visibility')->default('private');
            $table->unsignedSmallInteger('estimated_weeks')->nullable();
        });

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->date('scheduled_on')->nullable()->change();
            $table->unsignedSmallInteger('day_offset')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
        });

        Schema::create('program_phases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'program_phases_org_fk')->cascadeOnDelete();
            $table->foreignId('training_program_id')->constrained(indexName: 'program_phases_program_fk')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('duration_weeks')->nullable();
            $table->timestamps();
        });

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->foreignId('program_phase_id')->nullable()->after('training_program_id')->constrained(indexName: 'training_sessions_phase_fk')->nullOnDelete();
        });

        Schema::create('program_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'program_assignments_org_fk')->cascadeOnDelete();
            $table->foreignId('training_program_id')->constrained(indexName: 'program_assignments_program_fk')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users', indexName: 'program_assignments_athlete_fk')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users', indexName: 'program_assignments_assigner_fk')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('timezone')->default('Asia/Riyadh');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'athlete_id', 'status'], 'program_assignments_org_athlete_status_ix');
        });

        Schema::create('scheduled_workouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'scheduled_workouts_org_fk')->cascadeOnDelete();
            $table->foreignId('program_assignment_id')->constrained(indexName: 'scheduled_workouts_assignment_fk')->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained(indexName: 'scheduled_workouts_session_fk')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users', indexName: 'scheduled_workouts_athlete_fk')->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users', indexName: 'scheduled_workouts_coach_fk')->nullOnDelete();
            $table->dateTime('scheduled_for')->index();
            $table->string('status')->default('scheduled')->index();
            $table->text('coach_notes')->nullable();
            $table->text('athlete_notes')->nullable();
            $table->timestamps();
            $table->unique(['program_assignment_id', 'training_session_id', 'scheduled_for'], 'scheduled_workouts_source_unique');
        });

        Schema::create('exercise_library', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'exercise_library_org_fk')->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users', indexName: 'exercise_library_owner_fk')->nullOnDelete();
            $table->string('name');
            $table->string('section')->nullable();
            $table->string('movement_type')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('default_sets')->nullable();
            $table->string('default_reps')->nullable();
            $table->string('default_load')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedSmallInteger('default_rest_seconds')->nullable();
            $table->string('media_url')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->index(['organization_id', 'name'], 'exercise_library_org_name_ix');
        });

        Schema::create('training_session_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'session_exercises_org_fk')->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained(indexName: 'session_exercises_session_fk')->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained('exercise_library', indexName: 'session_exercises_exercise_fk')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('section')->nullable();
            $table->string('superset_label')->nullable();
            $table->string('name');
            $table->unsignedSmallInteger('target_sets')->default(1);
            $table->string('target_reps')->nullable();
            $table->string('target_load')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->string('media_url')->nullable();
            $table->string('movement_type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('workout_logs', function (Blueprint $table): void {
            $table->dropUnique('workout_logs_training_session_id_athlete_id_unique');
            $table->foreignId('program_assignment_id')->nullable()->constrained(indexName: 'workout_logs_assignment_fk')->nullOnDelete();
            $table->foreignId('scheduled_workout_id')->nullable()->constrained(indexName: 'workout_logs_schedule_fk')->nullOnDelete();
            $table->unsignedInteger('sync_version')->default(1);
            $table->unique(['scheduled_workout_id', 'athlete_id'], 'workout_logs_scheduled_athlete_unique');
        });

        Schema::create('workout_set_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'workout_sets_org_fk')->cascadeOnDelete();
            $table->foreignId('workout_log_id')->constrained(indexName: 'workout_sets_log_fk')->cascadeOnDelete();
            $table->foreignId('scheduled_workout_id')->nullable()->constrained(indexName: 'workout_sets_schedule_fk')->nullOnDelete();
            $table->foreignId('training_session_exercise_id')->nullable()->constrained(indexName: 'workout_sets_session_exercise_fk')->nullOnDelete();
            $table->foreignId('athlete_id')->constrained('users', indexName: 'workout_sets_athlete_fk')->cascadeOnDelete();
            $table->unsignedSmallInteger('exercise_index')->default(0);
            $table->string('exercise_name');
            $table->unsignedSmallInteger('set_number');
            $table->string('target_reps')->nullable();
            $table->string('target_load')->nullable();
            $table->unsignedSmallInteger('target_rest_seconds')->nullable();
            $table->decimal('actual_reps', 8, 2)->nullable();
            $table->decimal('actual_load', 9, 2)->nullable();
            $table->unsignedTinyInteger('actual_rpe')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['workout_log_id', 'exercise_index', 'set_number'], 'workout_set_logs_row_unique');
        });

        Schema::create('progress_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'progress_photos_org_fk')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users', indexName: 'progress_photos_athlete_fk')->cascadeOnDelete();
            $table->foreignId('progress_entry_id')->nullable()->constrained(indexName: 'progress_photos_entry_fk')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users', indexName: 'progress_photos_uploader_fk')->nullOnDelete();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('category')->default('progress');
            $table->string('visibility')->default('coaches');
            $table->date('taken_on')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('coach_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'coach_notes_org_fk')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users', indexName: 'coach_notes_coach_fk')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users', indexName: 'coach_notes_athlete_fk')->cascadeOnDelete();
            $table->text('body');
            $table->string('visibility')->default('private');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained(indexName: 'media_assets_org_fk')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users', indexName: 'media_assets_uploader_fk')->nullOnDelete();
            $table->nullableMorphs('attachable');
            $table->string('type');
            $table->string('disk')->default('public');
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'conversations_org_fk')->cascadeOnDelete();
            $table->string('type')->default('direct');
            $table->string('subject')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained(indexName: 'conversation_participants_conversation_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(indexName: 'conversation_participants_user_fk')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id'], 'conversation_participants_user_uq');
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained(indexName: 'messages_conversation_fk')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users', indexName: 'messages_sender_fk')->nullOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('messages', indexName: 'messages_reply_fk')->nullOnDelete();
            $table->text('body');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('personal_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained(indexName: 'personal_records_org_fk')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users', indexName: 'personal_records_athlete_fk')->cascadeOnDelete();
            $table->foreignId('workout_set_log_id')->nullable()->constrained(indexName: 'personal_records_workout_set_fk')->nullOnDelete();
            $table->string('exercise_name');
            $table->string('record_type')->default('load');
            $table->decimal('value', 10, 2);
            $table->string('unit')->default('kg');
            $table->date('achieved_on');
            $table->timestamps();
            $table->index(['organization_id', 'athlete_id', 'exercise_name'], 'personal_records_org_athlete_exercise_ix');
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $this->backfillExistingData();
    }

    private function backfillExistingData(): void
    {
        $now = now();
        $ownerId = DB::table('users')->where('role', 'owner')->value('id') ?? DB::table('users')->value('id');
        $organizationId = DB::table('organizations')->insertGetId([
            'owner_id' => $ownerId,
            'name' => 'Throughline',
            'slug' => 'throughline',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
            'default_theme' => 'system',
            'plan_key' => 'team',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')->orderBy('id')->get()->each(function (object $user) use ($organizationId, $now): void {
            $membershipRole = match ($user->role) {
                'owner' => 'organization_owner',
                'admin' => 'organization_admin',
                'coach' => 'coach',
                default => 'athlete',
            };

            DB::table('organization_memberships')->insert([
                'organization_id' => $organizationId,
                'user_id' => $user->id,
                'role' => $membershipRole,
                'status' => 'active',
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('users')->where('id', $user->id)->update(['current_organization_id' => $organizationId]);

            if ($membershipRole === 'coach') {
                DB::table('coach_profiles')->insert([
                    'organization_id' => $organizationId,
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($membershipRole === 'athlete') {
                DB::table('athlete_profiles')->insert([
                    'organization_id' => $organizationId,
                    'user_id' => $user->id,
                    'timezone' => 'Asia/Riyadh',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        foreach (['athlete_invitations', 'coach_athlete_assignments', 'training_programs', 'training_sessions', 'workout_logs', 'progress_entries', 'audit_logs', 'email_logs'] as $tableName) {
            DB::table($tableName)->update(['organization_id' => $organizationId]);
        }

        DB::table('training_programs')->orderBy('id')->get()->each(function (object $program) use ($organizationId, $now): void {
            $phaseId = DB::table('program_phases')->insertGetId([
                'organization_id' => $organizationId,
                'training_program_id' => $program->id,
                'title' => 'Foundation',
                'description' => 'Migrated program phase.',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $assignmentId = null;
            if ($program->athlete_id) {
                $assignmentId = DB::table('program_assignments')->insertGetId([
                    'organization_id' => $organizationId,
                    'training_program_id' => $program->id,
                    'athlete_id' => $program->athlete_id,
                    'assigned_by' => $program->coach_id,
                    'status' => $program->status === 'archived' ? 'completed' : 'active',
                    'starts_on' => $program->starts_on ?: $now->toDateString(),
                    'ends_on' => $program->ends_on,
                    'timezone' => 'Asia/Riyadh',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $startDate = $program->starts_on ? Carbon::parse($program->starts_on) : $now->copy()->startOfDay();

            DB::table('training_sessions')->where('training_program_id', $program->id)->orderBy('scheduled_on')->get()
                ->each(function (object $session, int $sessionIndex) use ($organizationId, $program, $phaseId, $assignmentId, $startDate, $now): void {
                    $scheduledOn = $session->scheduled_on ? Carbon::parse($session->scheduled_on) : $startDate->copy()->addDays($sessionIndex);
                    DB::table('training_sessions')->where('id', $session->id)->update([
                        'program_phase_id' => $phaseId,
                        'day_offset' => max(0, $startDate->diffInDays($scheduledOn, false)),
                        'sort_order' => $sessionIndex + 1,
                    ]);

                    $scheduledWorkoutId = null;
                    if ($assignmentId && $program->athlete_id) {
                        $scheduledWorkoutId = DB::table('scheduled_workouts')->insertGetId([
                            'organization_id' => $organizationId,
                            'program_assignment_id' => $assignmentId,
                            'training_session_id' => $session->id,
                            'athlete_id' => $program->athlete_id,
                            'coach_id' => $program->coach_id,
                            'scheduled_for' => $scheduledOn->startOfDay(),
                            'status' => $session->status === 'cancelled' ? 'skipped' : 'scheduled',
                            'coach_notes' => $session->coach_notes,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    $exercises = json_decode($session->exercises ?: '[]', true) ?: [];
                    foreach ($exercises as $exerciseIndex => $exercise) {
                        $rest = $exercise['rest_seconds'] ?? $exercise['rest'] ?? null;
                        $restSeconds = is_numeric($rest) ? (int) $rest : (preg_match('/\d+/', (string) $rest, $matches) ? (int) $matches[0] : null);
                        DB::table('training_session_exercises')->insert([
                            'organization_id' => $organizationId,
                            'training_session_id' => $session->id,
                            'sort_order' => $exerciseIndex + 1,
                            'section' => $exercise['section'] ?? 'Main work',
                            'superset_label' => $exercise['superset_label'] ?? null,
                            'name' => $exercise['name'] ?? 'Exercise',
                            'target_sets' => max(1, (int) ($exercise['sets'] ?? 1)),
                            'target_reps' => (string) ($exercise['reps'] ?? ''),
                            'target_load' => (string) ($exercise['load'] ?? ''),
                            'unit' => $exercise['unit'] ?? null,
                            'rest_seconds' => $restSeconds,
                            'notes' => $exercise['note'] ?? $exercise['notes'] ?? null,
                            'media_url' => $exercise['media_url'] ?? null,
                            'movement_type' => $exercise['movement_type'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('workout_logs')->where('training_session_id', $session->id)->get()
                        ->each(function (object $log) use ($organizationId, $assignmentId, $scheduledWorkoutId, $exercises, $now): void {
                            DB::table('workout_logs')->where('id', $log->id)->update([
                                'program_assignment_id' => $assignmentId,
                                'scheduled_workout_id' => $scheduledWorkoutId,
                            ]);

                            $setLogs = json_decode($log->set_logs ?: '[]', true) ?: [];
                            foreach ($setLogs as $exerciseIndex => $sets) {
                                $rows = isset($sets['sets']) ? $sets['sets'] : (is_array($sets) ? $sets : []);
                                foreach ($rows as $setIndex => $set) {
                                    DB::table('workout_set_logs')->insertOrIgnore([
                                        'organization_id' => $organizationId,
                                        'workout_log_id' => $log->id,
                                        'scheduled_workout_id' => $scheduledWorkoutId,
                                        'athlete_id' => $log->athlete_id,
                                        'exercise_index' => (int) $exerciseIndex,
                                        'exercise_name' => $exercises[$exerciseIndex]['name'] ?? 'Exercise '.((int) $exerciseIndex + 1),
                                        'set_number' => (int) $setIndex + 1,
                                        'actual_reps' => is_numeric($set['reps'] ?? null) ? $set['reps'] : null,
                                        'actual_load' => is_numeric($set['load'] ?? null) ? $set['load'] : null,
                                        'actual_rpe' => is_numeric($set['rpe'] ?? null) ? $set['rpe'] : null,
                                        'completed_at' => ! empty($set['completed']) ? ($log->completed_at ?: $now) : null,
                                        'notes' => $set['notes'] ?? null,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);
                                }
                            }
                        });
                });
        });
    }

    public function down(): void
    {
        Schema::table('progress_entries', function (Blueprint $table): void {
            $table->dropUnique('progress_entries_org_athlete_day_unique');
            $table->unique(['athlete_id', 'logged_on']);
        });

        Schema::table('coach_athlete_assignments', function (Blueprint $table): void {
            $table->dropUnique('coach_athlete_assignments_org_pair_unique');
            $table->unique(['coach_id', 'athlete_id']);
        });

        Schema::table('workout_logs', function (Blueprint $table): void {
            $table->dropUnique('workout_logs_scheduled_athlete_unique');
            $this->dropForeignKey($table, 'scheduled_workout_id', 'workout_logs_schedule_fk');
            $this->dropForeignKey($table, 'program_assignment_id', 'workout_logs_assignment_fk');
            $table->dropColumn(['scheduled_workout_id', 'program_assignment_id']);
            $table->dropColumn('sync_version');
            $table->unique(['training_session_id', 'athlete_id']);
        });

        Schema::table('training_sessions', function (Blueprint $table): void {
            $this->dropForeignKey($table, 'program_phase_id', 'training_sessions_phase_fk');
            $table->dropColumn('program_phase_id');
            $table->dropColumn(['day_offset', 'sort_order', 'estimated_minutes']);
        });

        foreach (['notifications', 'personal_records', 'messages', 'conversation_participants', 'conversations', 'media_assets', 'coach_notes', 'progress_photos', 'workout_set_logs', 'training_session_exercises', 'exercise_library', 'scheduled_workouts', 'program_assignments', 'program_phases', 'coach_profiles', 'athlete_profiles', 'organization_settings', 'membership_permission_overrides', 'organization_memberships'] as $tableName) {
            Schema::dropIfExists($tableName);
        }

        Schema::table('training_programs', function (Blueprint $table): void {
            $table->dropIndex(['is_template']);
            $table->dropColumn(['is_template', 'visibility', 'estimated_weeks']);
        });

        foreach (['athlete_invitations', 'coach_athlete_assignments', 'training_programs', 'training_sessions', 'workout_logs', 'progress_entries', 'audit_logs', 'email_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $this->dropForeignKey($table, 'organization_id', $tableName.'_org_fk');
                $table->dropColumn('organization_id');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $this->dropForeignKey($table, 'current_organization_id', 'users_current_org_fk');
            $table->dropColumn('current_organization_id');
            $table->dropColumn(['theme_preference', 'avatar_path']);
        });

        Schema::dropIfExists('organizations');
        Schema::dropIfExists('personal_access_tokens');
    }

    private function dropForeignKey(Blueprint $table, string $column, string $name): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $table->dropForeign([$column]);

            return;
        }

        $table->dropForeign($name);
    }
};

<?php

use App\Http\Controllers\Admin\ContactSubmissionExportController;
use App\Http\Controllers\Admin\InvitationExportController;
use App\Http\Controllers\Admin\LogExportController;
use App\Http\Controllers\Admin\OrganizationExportController;
use App\Http\Controllers\Admin\OrganizationMemberExportController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Admin\UserExportController;
use App\Http\Controllers\Athlete\ProgressPhotoController as AthleteProgressPhotoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Coach\AthleteProfileExportController;
use App\Http\Controllers\Coach\CoachReportExportController;
use App\Http\Controllers\Coach\ExerciseExportController;
use App\Http\Controllers\Coach\ProgramExportController;
use App\Http\Controllers\Coach\ProgressPhotoController;
use App\Http\Controllers\Coach\ScheduleExportController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\OrganizationSelectionController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ThemePreferenceController;
use App\Livewire\Admin;
use App\Livewire\Athlete;
use App\Livewire\Coach;
use App\Livewire\Invite;
use App\Livewire\Messaging\Inbox;
use App\Livewire\NotificationCenter;
use App\Livewire\Public\ContactForm;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');
Route::get('/features', [PublicPageController::class, 'features'])->name('features');
Route::get('/pricing', [PublicPageController::class, 'pricing'])->name('pricing');
Route::get('/contact', ContactForm::class)->name('contact');

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

Route::middleware(['auth', 'organization'])->group(function (): void {
    Route::post('/appearance', ThemePreferenceController::class)->name('appearance.update');
    Route::post('/organizations/{organization}/select', OrganizationSelectionController::class)->name('organizations.select');
    Route::get('/notifications', NotificationCenter::class)->middleware('can:notifications.read')->name('notifications');
    Route::middleware('can:admin.access')->prefix('admin')->name('admin.')->group(function (): void {
        Route::redirect('/', '/admin/dashboard')->name('root');
        Route::get('/dashboard', Admin\Dashboard::class)->name('dashboard');
        Route::get('/organizations/export', OrganizationExportController::class)->middleware('can:organizations.manage')->name('organizations.export');
        Route::get('/organizations', Admin\OrganizationsTable::class)->middleware('can:organizations.manage')->name('organizations');
        Route::get('/organizations/{organization}/members/export', OrganizationMemberExportController::class)->middleware('can:organizations.manage')->name('organizations.members.export');
        Route::get('/organizations/{organization}', Admin\OrganizationDetail::class)->middleware('can:organizations.manage')->name('organizations.show');
        Route::get('/users', Admin\UsersTable::class)->middleware('can:users.manage')->name('users');
        Route::get('/users/export', UserExportController::class)->name('users.export');
        Route::get('/users/{user}', Admin\UserDetail::class)->name('users.show');
        Route::get('/coaches', Admin\CoachesTable::class)->middleware('can:coaches.manage')->name('coaches');
        Route::get('/athletes', Admin\AthletesTable::class)->middleware('can:athletes.manage')->name('athletes');
        Route::get('/contact-submissions/export', ContactSubmissionExportController::class)->middleware('can:admin.contacts')->name('contact-submissions.export');
        Route::get('/contact-submissions', Admin\ContactSubmissionsTable::class)->middleware('can:admin.contacts')->name('contact-submissions');
        Route::get('/invitations/export', InvitationExportController::class)->middleware('can:invitations.manage')->name('invitations.export');
        Route::get('/invitations', Admin\InvitationsTable::class)->middleware('can:invitations.manage')->name('invitations');
        Route::get('/permissions', Admin\PermissionsPanel::class)->middleware('can:admin.permissions')->name('permissions');
        Route::get('/settings', Admin\SettingsPanel::class)->middleware('can:admin.settings')->name('settings');
        Route::get('/reports/export', ReportExportController::class)->middleware('can:reports.view')->name('reports.export');
        Route::get('/reports', Admin\Reports::class)->middleware('can:reports.view')->name('reports');
        Route::get('/email-logs/export', LogExportController::class)->defaults('tab', 'email')->middleware('can:admin.audit')->name('email-logs.export');
        Route::get('/email-logs', Admin\EmailLogsTable::class)->middleware('can:admin.audit')->name('email-logs');
        Route::get('/audit-log/export', LogExportController::class)->middleware('can:admin.audit')->name('audit.export');
        Route::get('/audit-log', Admin\AuditLogTable::class)->middleware('can:admin.audit')->name('audit');
    });

    Route::middleware('can:coach.access')->prefix('coach')->name('coach.')->group(function (): void {
        Route::get('/', Coach\Home::class)->name('home');
        Route::get('/athletes', Coach\AthletesTable::class)->middleware('can:athletes.view')->name('athletes');
        Route::get('/athletes/{athlete}/export/{section}', AthleteProfileExportController::class)->middleware('can:athletes.view')->name('athletes.export');
        Route::get('/athletes/{athlete}/photos/{photo}', ProgressPhotoController::class)->middleware('can:progress.review')->name('athletes.photos.view');
        Route::get('/athletes/{athlete}', Coach\AthleteDetail::class)->middleware('can:athletes.view')->name('athletes.show');
        Route::get('/programs/export', ProgramExportController::class)->middleware('can:programs.manage')->name('programs.export');
        Route::get('/programs', Coach\ProgramsTable::class)->name('programs');
        Route::get('/programs/{program}', Coach\ProgramDetail::class)->name('programs.show');
        Route::get('/exercises/export', ExerciseExportController::class)->middleware('can:exercises.manage')->name('exercises.export');
        Route::get('/exercises', Coach\ExerciseLibraryTable::class)->middleware('can:exercises.manage')->name('exercises');
        Route::get('/schedule/export', ScheduleExportController::class)->middleware('can:schedule.manage')->name('schedule.export');
        Route::get('/schedule', Coach\ScheduleTable::class)->middleware('can:schedule.manage')->name('schedule');
        Route::get('/reports/export', CoachReportExportController::class)->middleware('can:reports.view')->name('reports.export');
        Route::get('/reports', Coach\Reports::class)->middleware('can:reports.view')->name('reports');
        Route::get('/invitations', Coach\InvitationsPanel::class)->name('invitations');
        Route::get('/messages', Inbox::class)->middleware('can:messages.read')->name('messages');
    });

    Route::middleware('can:athlete.access')->prefix('app')->name('app.')->group(function (): void {
        Route::get('/', Athlete\Home::class)->name('home');
        Route::get('/programs/{assignment}', Athlete\ProgramDetail::class)->name('programs.show');
        Route::get('/workouts/{workout}', Athlete\WorkoutDetail::class)->name('workouts.show');
        Route::get('/progress', Athlete\ProgressPanel::class)->name('progress');
        Route::get('/progress/photos/{photo}', AthleteProgressPhotoController::class)->name('progress.photos.view');
        Route::get('/messages', Inbox::class)->middleware('can:messages.read')->name('messages');
        Route::get('/profile', Athlete\Profile::class)->name('profile');
    });

    Route::get('/messages/attachments/{media}', MessageAttachmentController::class)->middleware('can:messages.read')->name('messages.attachments');
});

Route::get('/invites/{token}', Invite\AcceptInvite::class)->name('invites.accept');

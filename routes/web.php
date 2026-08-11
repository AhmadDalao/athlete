<?php

use App\Http\Controllers\Admin\ContactSubmissionExportController;
use App\Http\Controllers\Admin\InvitationExportController;
use App\Http\Controllers\Admin\LogExportController;
use App\Http\Controllers\Admin\OrganizationExportController;
use App\Http\Controllers\Admin\OrganizationMemberExportController;
use App\Http\Controllers\Admin\UserExportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\OrganizationSelectionController;
use App\Http\Controllers\ThemePreferenceController;
use App\Livewire\Admin;
use App\Livewire\Athlete;
use App\Livewire\Coach;
use App\Livewire\Invite;
use App\Livewire\Public\ContactForm;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');
Route::view('/features', 'public.features')->name('features');
Route::view('/pricing', 'public.pricing')->name('pricing');
Route::get('/contact', ContactForm::class)->name('contact');

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

Route::middleware(['auth', 'organization'])->group(function (): void {
    Route::post('/appearance', ThemePreferenceController::class)->name('appearance.update');
    Route::post('/organizations/{organization}/select', OrganizationSelectionController::class)->name('organizations.select');
    Route::middleware('can:admin.access')->prefix('admin')->name('admin.')->group(function (): void {
        Route::redirect('/', '/admin/dashboard')->name('root');
        Route::get('/dashboard', Admin\Dashboard::class)->name('dashboard');
        Route::get('/organizations/export', OrganizationExportController::class)->middleware('can:organizations.manage')->name('organizations.export');
        Route::get('/organizations', Admin\OrganizationsTable::class)->middleware('can:organizations.manage')->name('organizations');
        Route::get('/organizations/{organization}/members/export', OrganizationMemberExportController::class)->middleware('can:organizations.manage')->name('organizations.members.export');
        Route::get('/organizations/{organization}', Admin\OrganizationDetail::class)->middleware('can:organizations.manage')->name('organizations.show');
        Route::get('/users', Admin\UsersTable::class)->name('users');
        Route::get('/users/export', UserExportController::class)->name('users.export');
        Route::get('/users/{user}', Admin\UserDetail::class)->name('users.show');
        Route::get('/coaches', Admin\CoachesTable::class)->name('coaches');
        Route::get('/athletes', Admin\AthletesTable::class)->name('athletes');
        Route::get('/contact-submissions/export', ContactSubmissionExportController::class)->middleware('can:admin.contacts')->name('contact-submissions.export');
        Route::get('/contact-submissions', Admin\ContactSubmissionsTable::class)->middleware('can:admin.contacts')->name('contact-submissions');
        Route::get('/invitations/export', InvitationExportController::class)->middleware('can:invitations.manage')->name('invitations.export');
        Route::get('/invitations', Admin\InvitationsTable::class)->name('invitations');
        Route::get('/permissions', Admin\PermissionsPanel::class)->name('permissions');
        Route::get('/settings', Admin\SettingsPanel::class)->name('settings');
        Route::get('/audit-log/export', LogExportController::class)->middleware('can:admin.audit')->name('audit.export');
        Route::get('/audit-log', Admin\AuditLogTable::class)->name('audit');
    });

    Route::middleware('can:coach.access')->prefix('coach')->name('coach.')->group(function (): void {
        Route::get('/', Coach\Home::class)->name('home');
        Route::get('/athletes', Coach\AthletesTable::class)->name('athletes');
        Route::get('/athletes/{athlete}', Coach\AthleteDetail::class)->name('athletes.show');
        Route::get('/programs', Coach\ProgramsTable::class)->name('programs');
        Route::get('/programs/{program}', Coach\ProgramDetail::class)->name('programs.show');
        Route::get('/invitations', Coach\InvitationsPanel::class)->name('invitations');
    });

    Route::middleware('can:athlete.access')->prefix('app')->name('app.')->group(function (): void {
        Route::get('/', Athlete\Home::class)->name('home');
        Route::get('/programs/{program}', Athlete\ProgramDetail::class)->name('programs.show');
        Route::get('/workouts/{session}', Athlete\WorkoutDetail::class)->name('workouts.show');
        Route::get('/progress', Athlete\ProgressPanel::class)->name('progress');
    });
});

Route::get('/invites/{token}', Invite\AcceptInvite::class)->name('invites.accept');

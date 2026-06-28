<?php

namespace App\Support;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Models\AthleteFile;
use App\Models\CoachAthleteAssignment;
use App\Models\User;

class AthleteAccess
{
    public static function canViewAthlete(User $viewer, User $athlete): bool
    {
        $viewer->loadMissing('roles');
        $athlete->loadMissing('roles');

        if (! $athlete->hasRole(RoleName::Athlete)) {
            return false;
        }

        if ($viewer->hasRole(RoleName::Owner) || $viewer->hasRole(RoleName::Admin)) {
            return $viewer->hasPermission('athletes.view');
        }

        if ($viewer->id === $athlete->id) {
            return true;
        }

        if (! $viewer->hasRole(RoleName::Coach) || ! $viewer->hasPermission('athletes.view')) {
            return false;
        }

        return self::isAssignedCoach($viewer, $athlete);
    }

    public static function canManageAthleteFiles(User $viewer, User $athlete): bool
    {
        $viewer->loadMissing('roles');

        if ($viewer->hasRole(RoleName::Owner) || $viewer->hasRole(RoleName::Admin)) {
            return $viewer->hasPermission('athlete.files.manage');
        }

        return $viewer->hasRole(RoleName::Coach)
            && $viewer->hasPermission('athlete.files.manage')
            && self::isAssignedCoach($viewer, $athlete);
    }

    public static function canViewAthleteFile(User $viewer, AthleteFile $file): bool
    {
        $file->loadMissing('athlete');
        $viewer->loadMissing('roles');

        if ($file->status !== AthleteFile::STATUS_ACTIVE && ! ($viewer->hasRole(RoleName::Owner) || $viewer->hasRole(RoleName::Admin))) {
            return false;
        }

        if ($viewer->hasRole(RoleName::Owner) || $viewer->hasRole(RoleName::Admin)) {
            return $viewer->hasPermission('athlete.files.view');
        }

        if ($viewer->id === $file->athlete_id) {
            return $file->visibility === AthleteFile::VISIBILITY_ATHLETE;
        }

        if ($file->visibility === AthleteFile::VISIBILITY_ADMIN) {
            return false;
        }

        return $viewer->hasRole(RoleName::Coach)
            && $viewer->hasPermission('athlete.files.view')
            && self::isAssignedCoach($viewer, $file->athlete);
    }

    public static function canInviteAthletes(User $viewer): bool
    {
        $viewer->loadMissing('roles');

        return ($viewer->hasRole(RoleName::Owner) || $viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Coach))
            && $viewer->hasPermission('roster.invite');
    }

    public static function isAssignedCoach(User $coach, User $athlete): bool
    {
        return CoachAthleteAssignment::query()
            ->where('coach_id', $coach->id)
            ->where('athlete_id', $athlete->id)
            ->whereIn('status', [CoachAthleteStatus::Active->value, CoachAthleteStatus::Paused->value])
            ->exists();
    }

    public static function hasActiveOtherCoach(User $coach, User $athlete): bool
    {
        return CoachAthleteAssignment::query()
            ->where('athlete_id', $athlete->id)
            ->where('coach_id', '!=', $coach->id)
            ->where('status', CoachAthleteStatus::Active->value)
            ->exists();
    }
}

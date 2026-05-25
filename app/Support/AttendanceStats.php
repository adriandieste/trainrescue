<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceStats
{
    public static function forUser(User $user): array
    {
        if (! self::supportsWorkoutsTable() || ! $user->club_id) {
            return self::emptyStats();
        }

        $eligibleSessions = (clone self::visibleWorkoutsQueryForUser($user))
            ->distinct('w.id')
            ->count('w.id');

        $completedSessions = self::supportsCompletionsTable()
            ? (clone self::completedWorkoutsQueryForUser($user))->count('wc.id')
            : 0;

        return [
            'eligible_sessions' => $eligibleSessions,
            'completed_sessions' => $completedSessions,
            'attendance_rate' => self::calculateRate($completedSessions, $eligibleSessions),
        ];
    }

    public static function addAttendanceSelects(EloquentBuilder|Relation $query, string $userTable = 'users'): EloquentBuilder|Relation
    {
        if (! self::supportsWorkoutsTable()) {
            return $query
                ->addSelect(DB::raw('0 as attendance_eligible_sessions'))
                ->addSelect(DB::raw('0 as attendance_completed_sessions'));
        }

        $query->addSelect([
            'attendance_eligible_sessions' => self::eligibleSessionsSubquery($userTable),
        ]);

        if (self::supportsCompletionsTable()) {
            $query->addSelect([
                'attendance_completed_sessions' => self::completedSessionsSubquery($userTable),
            ]);
        } else {
            $query->addSelect(DB::raw('0 as attendance_completed_sessions'));
        }

        return $query;
    }

    public static function calculateRate(int $completedSessions, int $eligibleSessions): int
    {
        if ($eligibleSessions <= 0) {
            return 0;
        }

        return max(0, min(100, (int) round(($completedSessions / $eligibleSessions) * 100)));
    }

    private static function visibleWorkoutsQueryForUser(User $user): QueryBuilder
    {
        $query = DB::table('workouts as w')
            ->where('w.club_id', $user->club_id)
            ->whereDate('w.workout_date', '<=', self::today())
            ->where(function (QueryBuilder $scope) use ($user) {
                $scope->where('w.target_scope', 'club');

                if (self::supportsAssignmentsTable()) {
                    $scope->orWhere(function (QueryBuilder $groupScope) use ($user) {
                        $groupScope
                            ->where('w.target_scope', 'grupo')
                            ->whereExists(function (QueryBuilder $assignmentQuery) use ($user) {
                                $assignmentQuery
                                    ->selectRaw('1')
                                    ->from('workout_assignments as wa')
                                    ->whereColumn('wa.workout_id', 'w.id')
                                    ->where('wa.user_id', $user->id);
                            });
                    });
                }
            });

        if (self::supportsTemplatesColumn()) {
            $query->where('w.is_template', false);
        }

        return $query;
    }

    private static function completedWorkoutsQueryForUser(User $user): QueryBuilder
    {
        $query = DB::table('workout_completions as wc')
            ->join('workouts as w', 'w.id', '=', 'wc.workout_id')
            ->where('wc.user_id', $user->id)
            ->where('w.club_id', $user->club_id)
            ->whereDate('w.workout_date', '<=', self::today())
            ->where(function (QueryBuilder $scope) use ($user) {
                $scope->where('w.target_scope', 'club');

                if (self::supportsAssignmentsTable()) {
                    $scope->orWhere(function (QueryBuilder $groupScope) use ($user) {
                        $groupScope
                            ->where('w.target_scope', 'grupo')
                            ->whereExists(function (QueryBuilder $assignmentQuery) use ($user) {
                                $assignmentQuery
                                    ->selectRaw('1')
                                    ->from('workout_assignments as wa')
                                    ->whereColumn('wa.workout_id', 'w.id')
                                    ->where('wa.user_id', $user->id);
                            });
                    });
                }
            });

        if (self::supportsTemplatesColumn()) {
            $query->where('w.is_template', false);
        }

        return $query;
    }

    private static function eligibleSessionsSubquery(string $userTable): QueryBuilder
    {
        $query = DB::table('workouts as w')
            ->selectRaw('COUNT(DISTINCT w.id)')
            ->whereColumn('w.club_id', $userTable . '.club_id')
            ->whereDate('w.workout_date', '<=', self::today())
            ->where(function (QueryBuilder $scope) use ($userTable) {
                $scope->where('w.target_scope', 'club');

                if (self::supportsAssignmentsTable()) {
                    $scope->orWhere(function (QueryBuilder $groupScope) use ($userTable) {
                        $groupScope
                            ->where('w.target_scope', 'grupo')
                            ->whereExists(function (QueryBuilder $assignmentQuery) use ($userTable) {
                                $assignmentQuery
                                    ->selectRaw('1')
                                    ->from('workout_assignments as wa')
                                    ->whereColumn('wa.workout_id', 'w.id')
                                    ->whereColumn('wa.user_id', $userTable . '.id');
                            });
                    });
                }
            });

        if (self::supportsTemplatesColumn()) {
            $query->where('w.is_template', false);
        }

        return $query;
    }

    private static function completedSessionsSubquery(string $userTable): QueryBuilder
    {
        $query = DB::table('workout_completions as wc')
            ->join('workouts as w', 'w.id', '=', 'wc.workout_id')
            ->selectRaw('COUNT(wc.id)')
            ->whereColumn('wc.user_id', $userTable . '.id')
            ->whereColumn('w.club_id', $userTable . '.club_id')
            ->whereDate('w.workout_date', '<=', self::today())
            ->where(function (QueryBuilder $scope) use ($userTable) {
                $scope->where('w.target_scope', 'club');

                if (self::supportsAssignmentsTable()) {
                    $scope->orWhere(function (QueryBuilder $groupScope) use ($userTable) {
                        $groupScope
                            ->where('w.target_scope', 'grupo')
                            ->whereExists(function (QueryBuilder $assignmentQuery) use ($userTable) {
                                $assignmentQuery
                                    ->selectRaw('1')
                                    ->from('workout_assignments as wa')
                                    ->whereColumn('wa.workout_id', 'w.id')
                                    ->whereColumn('wa.user_id', $userTable . '.id');
                            });
                    });
                }
            });

        if (self::supportsTemplatesColumn()) {
            $query->where('w.is_template', false);
        }

        return $query;
    }

    private static function emptyStats(): array
    {
        return [
            'eligible_sessions' => 0,
            'completed_sessions' => 0,
            'attendance_rate' => 0,
        ];
    }

    private static function today(): string
    {
        return Carbon::today()->toDateString();
    }

    private static function supportsWorkoutsTable(): bool
    {
        return Schema::hasTable('workouts');
    }

    private static function supportsCompletionsTable(): bool
    {
        return Schema::hasTable('workout_completions');
    }

    private static function supportsAssignmentsTable(): bool
    {
        return Schema::hasTable('workout_assignments');
    }

    private static function supportsTemplatesColumn(): bool
    {
        return self::supportsWorkoutsTable() && Schema::hasColumn('workouts', 'is_template');
    }
}


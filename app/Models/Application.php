<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Support\ServiceSchema;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'application_code',
    'citizen_id',
    'service_type_id',
    'assigned_staff_id',
    'status',
    'form_data',
    'submitted_at',
    'processing_started_at',
    'completed_at',
    'result_note',
    'rejection_reason',
])]
class Application extends Model
{
    use HasFactory, SoftDeletes;

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function historicalCitizen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'citizen_id')->withTrashed();
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function historicalServiceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id')->withTrashed();
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function historicalAssignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id')->withTrashed();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class)->chronological();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ApplicationAssignment::class)->chronological();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->chronological();
    }

    public function activeAssignment(): ?ApplicationAssignment
    {
        return $this->assignments
            ->first(fn (ApplicationAssignment $assignment) => $assignment->ended_at === null);
    }

    public function isOverdue(): bool
    {
        if ($this->status->isTerminal()) {
            return false;
        }

        $processingTimeDays = (int) ($this->serviceType?->processing_time_days ?? 0);

        return $this->submitted_at !== null
            && $this->submitted_at->copy()->addDays($processingTimeDays)->isPast();
    }

    public function supplementNote(): ?string
    {
        $latest = $this->statusHistories
            ->filter(fn (ApplicationStatusHistory $history) => $history->to_status === ApplicationStatus::SupplementRequired)
            ->sortByDesc(fn (ApplicationStatusHistory $history) => $history->created_at?->timestamp ?? 0)
            ->first();

        return $latest?->note;
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function missingRequiredDocuments(): array
    {
        if (! in_array($this->status, [ApplicationStatus::Received, ApplicationStatus::SupplementRequired], true)) {
            return [];
        }

        if (! $this->relationLoaded('serviceType') || ! $this->relationLoaded('documents')) {
            return [];
        }

        $requirements = ServiceSchema::normalizeDocumentRequirements($this->serviceType->document_requirements);

        $uploadedCodes = $this->documents
            ->pluck('requirement_code')
            ->filter()
            ->flip();

        $missing = [];

        foreach ($requirements as $requirement) {
            if ($requirement['required'] && ! $uploadedCodes->has($requirement['code'])) {
                $missing[] = ['code' => $requirement['code'], 'label' => $requirement['label']];
            }
        }

        return $missing;
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if (! $actor->canAccessProtectedResources()) {
            return $query->whereRaw('1 = 0');
        }

        if ($actor->isSuperAdmin()) {
            return $query;
        }

        if ($actor->isManager()) {
            $departmentIds = $actor->ledDepartments()->pluck('id');

            return $query->whereHas(
                'serviceType',
                fn (Builder $serviceQuery): Builder => $serviceQuery
                    ->withTrashed()
                    ->whereIn('responsible_department_id', $departmentIds)
            );
        }

        if ($actor->isStaff()) {
            return $query->where('assigned_staff_id', $actor->getKey());
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeSearchForAdmin(Builder $query, ?string $keyword): Builder
    {
        if ($keyword === null) {
            return $query;
        }

        $pattern = '%'.self::escapeLikePattern($keyword).'%';

        return $query->where(function (Builder $searchQuery) use ($pattern): void {
            $searchQuery
                ->whereRaw("applications.application_code COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                ->orWhereHas('citizen', function (Builder $citizenQuery) use ($pattern): void {
                    $citizenQuery
                        ->withTrashed()
                        ->where(function (Builder $identityQuery) use ($pattern): void {
                            $identityQuery
                                ->whereRaw("users.name COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                                ->orWhereRaw("users.citizen_id COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern]);
                        });
                })
                ->orWhereHas('serviceType', fn (Builder $serviceQuery): Builder => $serviceQuery
                    ->withTrashed()
                    ->whereRaw("service_types.name COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern]));
        });
    }

    public function scopeWithAdminStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null) {
            return $query;
        }

        return $status === ApplicationStatus::COMPLETED_FILTER
            ? $query->whereIn('status', ApplicationStatus::completedValues())
            : $query->where('status', $status);
    }

    public function scopeForService(Builder $query, ?int $serviceTypeId): Builder
    {
        return $serviceTypeId === null
            ? $query
            : $query->where('service_type_id', $serviceTypeId);
    }

    public function scopeForDepartment(Builder $query, ?int $departmentId): Builder
    {
        if ($departmentId === null) {
            return $query;
        }

        return $query->whereHas(
            'serviceType',
            fn (Builder $serviceQuery): Builder => $serviceQuery
                ->withTrashed()
                ->where('responsible_department_id', $departmentId),
        );
    }

    public function scopeAssignedToStaff(Builder $query, ?int $staffId): Builder
    {
        return $staffId === null
            ? $query
            : $query->where('assigned_staff_id', $staffId);
    }

    public function scopeSubmittedBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        $timezone = (string) config('app.timezone', 'UTC');

        if ($from !== null) {
            $query->where('submitted_at', '>=', CarbonImmutable::createFromFormat('!Y-m-d', $from, $timezone));
        }

        if ($to !== null) {
            $query->where('submitted_at', '<', CarbonImmutable::createFromFormat('!Y-m-d', $to, $timezone)->addDay());
        }

        return $query;
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', ApplicationStatus::completedValues())
            ->whereHas('serviceType', fn (Builder $serviceQuery): Builder => $serviceQuery
                ->withTrashed()
                ->whereRaw(self::overdueConditionSql()));
    }

    /**
     * Shared PostgreSQL predicate for list and dashboard overdue calculations.
     *
     * Both calling queries expose the service_types table under its canonical
     * name so the operational definition cannot drift between drill-downs and
     * aggregate metrics.
     */
    public static function overdueConditionSql(): string
    {
        return 'applications.submitted_at IS NOT NULL'
            .' AND service_types.processing_time_days IS NOT NULL'
            .' AND applications.submitted_at'
            .' + make_interval(days => service_types.processing_time_days::integer)'
            .' < CURRENT_TIMESTAMP';
    }

    public function scopeSortForAdmin(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->orderBy('submitted_at')->orderBy('id'),
            'code_asc' => $query->orderBy('application_code')->orderBy('id'),
            'code_desc' => $query->orderByDesc('application_code')->orderByDesc('id'),
            'status_asc' => $query->orderBy('status')->orderBy('id'),
            'status_desc' => $query->orderByDesc('status')->orderByDesc('id'),
            default => $query->orderByRaw('submitted_at DESC NULLS LAST')->orderByDesc('id'),
        };
    }

    public function scopeClaimableBy(Builder $query, User $actor): Builder
    {
        $departmentIds = $actor->departments()->pluck('departments.id');

        return $query
            ->whereNull('assigned_staff_id')
            ->where('status', ApplicationStatus::Received)
            ->whereHas(
                'serviceType',
                fn (Builder $serviceQuery) => $serviceQuery->whereIn('responsible_department_id', $departmentIds)
            );
    }

    public function scopeAssignableTo(Builder $query, User $actor): Builder
    {
        $terminal = ApplicationStatus::completedValues();

        if ($actor->isSuperAdmin()) {
            return $query->whereNotIn('status', $terminal);
        }

        if ($actor->isManager()) {
            $departmentIds = $actor->ledDepartments()->pluck('id');

            return $query
                ->whereNotIn('status', $terminal)
                ->whereHas(
                    'serviceType',
                    fn (Builder $serviceQuery) => $serviceQuery->whereIn('responsible_department_id', $departmentIds)
                );
        }

        return $query->whereRaw('1 = 0');
    }

    private static function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'form_data' => 'array',
            'submitted_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'actor_id',
    'action',
    'subject_type',
    'subject_id',
    'description',
    'metadata',
    'ip_address',
    'user_agent',
])]
class ActivityLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForActor(Builder $query, ?int $actorId): Builder
    {
        return $actorId === null
            ? $query
            : $query->where('actor_id', $actorId);
    }

    public function scopeForAction(Builder $query, ?string $action): Builder
    {
        return $action === null
            ? $query
            : $query->where('action', $action);
    }

    public function scopeForSubjectType(Builder $query, ?string $subjectType): Builder
    {
        return $subjectType === null
            ? $query
            : $query->where('subject_type', $subjectType);
    }

    public function scopeCreatedBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from !== null) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    public function scopeSearchKeyword(Builder $query, ?string $keyword): Builder
    {
        if ($keyword === null) {
            return $query;
        }

        $pattern = '%'.self::escapeLikePattern($keyword).'%';

        return $query->where(function (Builder $searchQuery) use ($pattern): void {
            $searchQuery
                ->whereRaw("activity_logs.action COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                ->orWhereRaw("activity_logs.description COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                ->orWhereRaw("activity_logs.metadata::text COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                ->orWhereHas('actor', function (Builder $actorQuery) use ($pattern): void {
                    $actorQuery
                        ->withTrashed()
                        ->where(function (Builder $identityQuery) use ($pattern): void {
                            $identityQuery
                                ->whereRaw("users.name COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                                ->orWhereRaw("users.email COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern]);
                        });
                });
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    private static function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

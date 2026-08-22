<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id',
    'responsible_department_id',
    'name',
    'code',
    'description',
    'requirements',
    'form_schema',
    'document_requirements',
    'processing_time_days',
    'fee',
    'is_active',
])]
class ServiceType extends Model
{
    use HasFactory, SoftDeletes;

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id')->withTrashed();
    }

    public function responsibleDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'responsible_department_id');
    }

    public function historicalResponsibleDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'responsible_department_id')->withTrashed();
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_staff', 'service_type_id', 'staff_id')
            ->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isActive(): bool
    {
        return ! $this->trashed() && $this->is_active;
    }

    public function isArchived(): bool
    {
        return $this->trashed();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->withTrashed();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'form_schema' => 'array',
            'document_requirements' => 'array',
            'fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}

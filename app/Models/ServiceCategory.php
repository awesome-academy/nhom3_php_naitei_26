<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'description'])]
class ServiceCategory extends Model
{
    use HasFactory, SoftDeletes;

    public function serviceTypes(): HasMany
    {
        return $this->hasMany(ServiceType::class, 'category_id');
    }

    public function isActive(): bool
    {
        return ! $this->trashed();
    }

    public function isArchived(): bool
    {
        return $this->trashed();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->withTrashed();
    }
}

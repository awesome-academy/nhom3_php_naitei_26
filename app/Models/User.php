<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'citizen_id',
    'date_of_birth',
    'gender',
    'phone',
    'address',
    'email_notifications_enabled',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public function isCitizen(): bool
    {
        return $this->role === UserRole::Citizen;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function canAccessProtectedResources(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    public function scopeEligibleDepartmentMembers(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Staff->value, UserRole::Manager->value]);
    }

    public function scopeEligibleDepartmentStaff(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('role', UserRole::Staff->value);
    }

    public function scopeAvailableDepartmentStaff(Builder $query): Builder
    {
        return $query
            ->eligibleDepartmentStaff()
            ->whereDoesntHave('departments');
    }

    public function scopeEligibleDepartmentLeaders(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('role', UserRole::Manager->value);
    }

    public function scopeAvailableDepartmentLeaders(Builder $query): Builder
    {
        return $query
            ->eligibleDepartmentLeaders()
            ->whereDoesntHave('departments')
            ->whereDoesntHave('ledDepartments');
    }

    public function submittedApplications(): HasMany
    {
        return $this->hasMany(Application::class, 'citizen_id');
    }

    public function assignedApplications(): HasMany
    {
        return $this->hasMany(Application::class, 'assigned_staff_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
    }

    public function ledDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'leader_id');
    }

    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceType::class, 'service_staff', 'staff_id', 'service_type_id')
            ->withTimestamps();
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'uploaded_by');
    }

    public function applicationAssignments(): HasMany
    {
        return $this->hasMany(ApplicationAssignment::class, 'staff_id');
    }

    public function assignmentActions(): HasMany
    {
        return $this->hasMany(ApplicationAssignment::class, 'assigned_by');
    }

    public function applicationStatusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'changed_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'date_of_birth' => 'date',
            'email_notifications_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

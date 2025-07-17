<?php

// app/Models/User.php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'role',
        'cabang_id',
        'jabatan_id',
        'atasan_id',
        'phone',
        'address',
        'hire_date',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function bawahan(): HasMany
    {
        return $this->hasMany(User::class, 'atasan_id');
    }

    public function auditSessions(): HasMany
    {
        return $this->hasMany(AuditSession::class, 'employee_id');
    }

    public function auditorSessions(): HasMany
    {
        return $this->hasMany(AuditSession::class, 'auditor_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByCabang($query, $cabangId)
    {
        return $query->where('cabang_id', $cabangId);
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->name . ' (' . $this->employee_id . ')';
    }

    public function canAudit($employee)
    {
        // CEO can audit everyone
        if ($this->role === 'CEO') {
            return true;
        }

        // CBO can audit Manager and below
        if ($this->role === 'CBO' && in_array($employee->role, ['Manager', 'SBC', 'BC', 'Trainee'])) {
            return true;
        }

        // Manager can audit SBC and below in their branch
        if ($this->role === 'Manager' && in_array($employee->role, ['SBC', 'BC', 'Trainee']) && $this->cabang_id === $employee->cabang_id) {
            return true;
        }

        // SBC can audit BC and Trainee under their supervision
        if ($this->role === 'SBC' && in_array($employee->role, ['BC', 'Trainee']) && $employee->atasan_id === $this->id) {
            return true;
        }

        // BC can audit Trainee under their supervision
        if ($this->role === 'BC' && $employee->role === 'Trainee' && $employee->atasan_id === $this->id) {
            return true;
        }

        return false;
    }

        public function getAuditableEmployees()
    {
        $query = User::active();

        switch ($this->role) {
            case 'CEO':
                $query->where('id', '!=', $this->id);
                break;
            case 'CBO':
                $query->whereIn('role', ['Manager', 'SBC', 'BC', 'Trainee']);
                break;
            case 'Manager':
                $query->whereIn('role', ['SBC', 'BC', 'Trainee'])
                    ->where('cabang_id', $this->cabang_id);
                break;
            case 'SBC':
                $query->whereIn('role', ['BC', 'Trainee'])
                    ->where('atasan_id', $this->id);
                break;
            case 'BC':
                $query->where('role', 'Trainee')
                    ->where('atasan_id', $this->id);
                break;
            default:
                $query->whereRaw('1 = 0');
                break;
        }

        return $query->with(['cabang', 'jabatan', 'atasan'])->paginate(10);
    }

            // User.php
        public function activities(): HasMany
        {
            return $this->hasMany(ActivityLog::class);
        }


}
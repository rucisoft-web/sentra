<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'mfa_secret', 'api_token'];

    public const ROLES = [
        'owner' => 'Owner / Komisaris',
        'bod_gm' => 'BOD / General Manager',
        'doc_controller' => 'Corporate Secretary / Document Controller',
        'legal' => 'Legal & Compliance',
        'finance' => 'Finance & Tax',
        'hr' => 'Human Resources',
        'dept_head' => 'Kepala Departemen',
        'staff' => 'Staf / Uploader',
        'it_admin' => 'IT / Security Admin',
        'auditor' => 'Internal / External Auditor',
        'service' => 'Service Account (Integrasi)',
    ];

    /** Peran yang wajib MFA (step-up) untuk aksi sensitif. */
    public const MFA_ROLES = ['owner', 'bod_gm', 'doc_controller', 'legal', 'finance', 'it_admin'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'access_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'bool',
            'can_bulk_export' => 'bool',
        ];
    }

    public function entity() { return $this->belongsTo(Entity::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function mandates() { return $this->hasMany(AuditMandate::class, 'auditor_id'); }
    public function notificationsList() { return $this->hasMany(AppNotification::class)->latest(); }

    public function hasRole(string ...$roles): bool { return in_array($this->role, $roles, true); }
    public function roleLabel(): string { return self::ROLES[$this->role] ?? $this->role; }

    public function isAccessExpired(): bool
    {
        return $this->access_expires_at && $this->access_expires_at->isPast();
    }

    public function isDeptHead(): bool
    {
        return $this->department && $this->department->head_user_id === $this->id;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', preg_replace('/^(Ir\.|Drs\.|H\.|Dr\.)\s*/', '', $this->name));
        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    public function canAdminister(): bool { return $this->hasRole('it_admin', 'doc_controller'); }
    public function canManageRecords(): bool { return $this->hasRole('doc_controller', 'legal'); }
}

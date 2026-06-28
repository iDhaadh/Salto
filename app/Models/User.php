<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isViewer(): bool   { return $this->role === 'viewer'; }
    public function isStaff(): bool    { return $this->role === 'staff'; }
    public function isOperator(): bool { return $this->role === 'operator'; }

    public function canAccessDoorEvents(): bool
    {
        return in_array($this->role, ['admin', 'operator', 'staff']);
    }

    public function canAccessDoors(): bool
    {
        return in_array($this->role, ['admin', 'operator']);
    }
}

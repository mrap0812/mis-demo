<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use Concerns\Auditable;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, array_column(Role::cases(), 'value'), true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin->value;
    }

    /** Admin atau bendahara: boleh mengajukan dan menyetujui (orang berbeda) perubahan rekening/QRIS. */
    public function isTreasurer(): bool
    {
        return in_array($this->role, [Role::Admin->value, Role::Bendahara->value], true);
    }

    public function isReviewer(): bool
    {
        return in_array($this->role, [Role::Admin->value, Role::Peninjau->value], true);
    }
}

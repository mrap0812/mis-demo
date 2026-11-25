<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\KajianSession;
use App\Models\User;

class KajianSessionPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, KajianSession $s): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, KajianSession $s): bool { return true; }
    public function delete(User $user, KajianSession $s): bool { return in_array($user->role, [Role::Admin->value, Role::Editor->value], true); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, KajianSession $s): bool { return false; }
}

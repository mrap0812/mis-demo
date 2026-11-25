<?php

namespace App\Policies;

use App\Models\Speaker;
use App\Models\User;

class SpeakerPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Speaker $s): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Speaker $s): bool { return true; }
    public function delete(User $user, Speaker $s): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, Speaker $s): bool { return false; }
}

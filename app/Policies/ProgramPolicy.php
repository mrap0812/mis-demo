<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

/** Program memuat jadwal dan nomor kontak publik; hanya peninjau dan admin yang boleh mengubah atau menayangkan. */
class ProgramPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Program $p): bool { return true; }
    public function create(User $user): bool { return $user->isReviewer(); }
    public function update(User $user, Program $p): bool { return $user->isReviewer(); }
    public function reorder(User $user): bool { return $user->isReviewer(); }
    public function delete(User $user, Program $p): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, Program $p): bool { return false; }
}

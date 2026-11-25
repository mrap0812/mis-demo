<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/** Layanan memuat nomor kontak yang dipakai jamaah; hanya peninjau dan admin yang boleh mengubahnya. */
class ServicePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Service $s): bool { return true; }
    public function create(User $user): bool { return $user->isReviewer(); }
    public function update(User $user, Service $s): bool { return $user->isReviewer(); }
    public function reorder(User $user): bool { return $user->isReviewer(); }
    public function delete(User $user, Service $s): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, Service $s): bool { return false; }
}

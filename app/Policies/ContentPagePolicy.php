<?php

namespace App\Policies;

use App\Models\ContentPage;
use App\Models\User;

/** Halaman statis tampil langsung ke publik, jadi hanya peninjau dan admin yang boleh mengubahnya. */
class ContentPagePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, ContentPage $p): bool { return true; }
    public function create(User $user): bool { return $user->isReviewer(); }
    public function update(User $user, ContentPage $p): bool { return $user->isReviewer(); }
    public function delete(User $user, ContentPage $p): bool { return $user->isAdmin() && ! $p->isReserved(); }
    public function deleteAny(User $user): bool { return false; }
    public function forceDelete(User $user, ContentPage $p): bool { return false; }
}

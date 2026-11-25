<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

/** Kampanye memuat angka dana dan rujukan rekening: hanya bendahara dan admin yang mengelola. */
class CampaignPolicy
{
    public function viewAny(User $user): bool { return $user->isTreasurer(); }
    public function view(User $user, Campaign $c): bool { return $user->isTreasurer(); }
    public function create(User $user): bool { return $user->isTreasurer(); }
    public function update(User $user, Campaign $c): bool { return $user->isTreasurer(); }
    public function reorder(User $user): bool { return $user->isTreasurer(); }
    public function delete(User $user, Campaign $c): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return false; }
    public function forceDelete(User $user, Campaign $c): bool { return false; }
}

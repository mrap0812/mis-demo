<?php

namespace App\Policies;

use App\Models\DonationAccount;
use App\Models\User;

/** Rekening tidak pernah diubah langsung; semua lewat pengajuan (DonationAccountChange). */
class DonationAccountPolicy
{
    public function viewAny(User $user): bool { return $user->isTreasurer(); }
    public function view(User $user, DonationAccount $a): bool { return $user->isTreasurer(); }
    public function create(User $user): bool { return false; }
    public function update(User $user, DonationAccount $a): bool { return false; }
    public function delete(User $user, DonationAccount $a): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
    public function forceDelete(User $user, DonationAccount $a): bool { return false; }
}

<?php

namespace App\Policies;

use App\Models\DonationAccountChange;
use App\Models\User;

class DonationAccountChangePolicy
{
    public function viewAny(User $user): bool { return $user->isTreasurer(); }
    public function view(User $user, DonationAccountChange $c): bool { return $user->isTreasurer(); }
    public function create(User $user): bool { return false; }
    public function update(User $user, DonationAccountChange $c): bool { return false; }
    public function approve(User $user, DonationAccountChange $c): bool { return $c->canBeApprovedBy($user); }
    public function cancel(User $user, DonationAccountChange $c): bool { return $c->status === 'menunggu' && ((int) $c->proposed_by === (int) $user->id || $user->isAdmin()); }
    public function delete(User $user, DonationAccountChange $c): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
    public function forceDelete(User $user, DonationAccountChange $c): bool { return false; }
}

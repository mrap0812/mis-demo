<?php

namespace App\Policies;

use App\Models\FinancialReport;
use App\Models\User;

/** Bendahara dan admin menyusun laporan; persetujuan oleh admin atau peninjau yang bukan pengaju (prinsip empat mata). */
class FinancialReportPolicy
{
    public function viewAny(User $user): bool { return $user->isTreasurer() || $user->isReviewer(); }
    public function view(User $user, FinancialReport $r): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->isTreasurer(); }
    public function update(User $user, FinancialReport $r): bool { return $user->isTreasurer(); }
    public function delete(User $user, FinancialReport $r): bool { return $user->isAdmin() && $r->status !== 'terbit'; }
    public function deleteAny(User $user): bool { return false; }
    public function forceDelete(User $user, FinancialReport $r): bool { return false; }

    public function approve(User $user, FinancialReport $r): bool
    {
        return $r->status === 'diajukan' && $user->isReviewer() && $user->id !== $r->submitted_by;
    }
}

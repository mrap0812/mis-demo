<?php

namespace App\Policies;

use App\Models\KajianSeries;
use App\Models\User;

/** Semua pengurus panel boleh menyunting jadwal. Menghapus seri (berantai ke pertemuannya) hanya admin; pilihan aman: nonaktifkan. */
class KajianSeriesPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, KajianSeries $s): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, KajianSeries $s): bool { return true; }
    public function delete(User $user, KajianSeries $s): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, KajianSeries $s): bool { return false; }
}

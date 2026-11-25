<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Penyiapan akun admin pertama tanpa SSH. Halaman hanya ada bila:
 * (1) SETUP_TOKEN di .env diisi minimal 24 karakter, dan (2) belum ada satu pun pengguna.
 * Setelah admin pertama dibuat, halaman ini otomatis 404 selamanya.
 */
class SetupController extends Controller
{
    private function available(): bool
    {
        return strlen((string) config('mis.setup_token')) >= 24 && ! User::query()->exists();
    }

    public function show()
    {
        abort_unless($this->available(), 404);

        return response()->view('site.setup')->header('X-Robots-Tag', 'noindex');
    }

    public function store(Request $request)
    {
        abort_unless($this->available(), 404);
        $data = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ], [], ['token' => 'token penyiapan', 'name' => 'nama', 'password' => 'kata sandi']);

        if (! hash_equals((string) config('mis.setup_token'), $data['token'])) {
            return back()->withInput($request->only('name', 'email'))->withErrors(['token' => 'Token penyiapan tidak cocok dengan SETUP_TOKEN di file .env.']);
        }

        User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => Role::Admin->value]);

        return redirect('/'.config('mis.admin_path').'/login')
            ->with('status', 'Akun admin dibuat. Hapus baris SETUP_TOKEN dari .env lalu masuk.');
    }
}

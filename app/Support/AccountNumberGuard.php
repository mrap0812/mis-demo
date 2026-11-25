<?php

namespace App\Support;

use App\Models\DonationAccount;
use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Mendeteksi deretan angka yang mirip nomor rekening di teks bebas (berita, halaman, layanan, pengumuman).
 * Tujuan: nomor rekening hanya tampil lewat menu Infak yang memakai persetujuan ganda.
 *
 * Ini heuristik, bukan pembuktian. Yang diabaikan: nomor telepon (08..., 628...), nomor kontak resmi di Pengaturan situs,
 * nominal uang bertitik ribuan (1.500.000), tanggal (2026-10-12, 12/10/2026), dan rekening yang sudah disetujui.
 */
final class AccountNumberGuard
{
    /** @return list<array{raw:string, digits:string, reason:string}> */
    public static function scan(mixed ...$texts): array
    {
        $text = collect($texts)->map(fn ($t) => is_array($t) ? json_encode($t, JSON_UNESCAPED_UNICODE) : (string) $t)->implode("\n");
        $text = html_entity_decode(strip_tags(str_replace(['<', '>'], [' <', '> '], $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (! preg_match_all('/(?<![\d\w])(\d[\d .\-]{5,26}\d)(?![\d\w])/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $accounts = DonationAccount::query()->whereNotNull('account_number')->get(['account_number', 'is_active'])
            ->mapWithKeys(fn ($a) => [SafeHtml::digits($a->account_number) => $a->is_active]);
        $contacts = collect(Setting::get('contacts', []))->pluck('number')->push(Setting::get('fab_number'))
            ->map(fn ($n) => SafeHtml::digits($n))->filter()->all();

        $found = [];
        foreach ($m[1] as [$raw, $offset]) {
            $raw = trim($raw);
            $d = SafeHtml::digits($raw);
            $len = strlen($d);
            if ($len < 7 || $len > 20) {
                continue;
            }
            if (preg_match('/^(08|628)\d{8,12}$/', $d) || in_array($d, $contacts, true)) {
                continue; // nomor telepon / WhatsApp
            }
            if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $raw) || preg_match('/Rp\.?\s*$/i', substr($text, max(0, $offset - 5), 5))) {
                continue; // nominal uang
            }
            if (preg_match('/^(\d{4}[-.\/ ]\d{1,2}[-.\/ ]\d{1,2}|\d{1,2}[-.\/ ]\d{1,2}[-.\/ ]\d{4})$/', $raw)) {
                continue; // tanggal
            }
            if ($accounts->has($d) && $accounts[$d]) {
                continue; // rekening resmi yang sudah disetujui
            }
            $found[$d] = ['raw' => $raw, 'digits' => $d, 'reason' => $accounts->has($d) ? 'sudah dinonaktifkan' : 'tidak terdaftar di menu Infak'];
        }

        return array_values($found);
    }

    public static function describe(array $found): string
    {
        return collect($found)->map(fn ($f) => $f['raw'].' ('.$f['reason'].')')->implode(', ');
    }

    /**
     * Aturan validasi untuk kolom yang langsung tayang tanpa alur tinjauan.
     * Berupa objek ValidationRule (bukan closure) agar Filament tidak mencoba mengevaluasinya sebagai closure miliknya.
     */
    public static function rule(): ValidationRule
    {
        return new class implements ValidationRule
        {
            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                if ($found = AccountNumberGuard::scan($value)) {
                    $fail('Ada nomor yang mirip rekening dan belum disetujui di menu Infak: '.AccountNumberGuard::describe($found)
                        .'. Nomor rekening hanya boleh tampil lewat halaman Infak; ganti dengan tautan /infak, atau ajukan rekeningnya di menu Infak.');
                }
            }
        };
    }
}

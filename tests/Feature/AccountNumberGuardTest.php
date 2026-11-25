<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\DonationAccountChange;
use App\Models\Post;
use App\Models\User;
use App\Support\AccountNumberGuard;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountNumberGuardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_mendeteksi_rekening_dan_mengabaikan_telepon_uang_tanggal(): void
    {
        $found = AccountNumberGuard::scan('<p>Rekening kurban BSI 700-7000-479 a.n. Imam. Konfirmasi 0811-517-4444 atau +62 811 500 822.</p>'
            .'<p>Biaya Rp 2.500.000 per orang, tanggal 2026-10-12, kode 12345.</p>');
        $this->assertSame(['7007000479'], array_column($found, 'digits'));
    }

    public function test_rekening_yang_disetujui_tidak_ditandai_dan_yang_nonaktif_ditandai(): void
    {
        $admin = User::where('role', 'admin')->first();
        $acc = DonationAccountChange::first()->approve($admin, '4004000949');
        $this->assertSame([], AccountNumberGuard::scan('Transfer ke BSI 400 4000 949'));

        DonationAccountChange::propose(User::where('role', 'bendahara')->first(), 'retire', $acc, [], 'uji')->approve($admin, 'NONAKTIFKAN');
        $this->assertSame('sudah dinonaktifkan', AccountNumberGuard::scan('Transfer ke BSI 400 4000 949')[0]['reason']);
    }

    public function test_berita_impor_yang_memuat_rekening_kurban_terdeteksi(): void
    {
        $risky = Post::published()->get()->filter(fn ($p) => AccountNumberGuard::scan($p->title, $p->excerpt, $p->body));
        $this->assertSame(['Sudah punya niat berkurban tahun depan?'], $risky->pluck('title')->values()->all());
    }

    public function test_menerbitkan_tulisan_bernomor_rekening_wajib_konfirmasi(): void
    {
        $editor = User::where('role', 'editor')->first();
        $post = Post::create(['type' => 'pengumuman', 'title' => 'Infak renovasi', 'body' => '<p>Transfer ke BRI 0123-01-000555-30-1</p>',
            'status' => PostStatus::Ditinjau, 'author_id' => $editor->id]);
        $this->actingAs(User::where('role', 'peninjau')->first());
        Filament::setCurrentPanel('admin');

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction('publish', ['ack_accounts' => false])
            ->assertHasActionErrors(['ack_accounts']);
        $this->assertSame(PostStatus::Ditinjau, $post->fresh()->status);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction('publish', ['ack_accounts' => true]);
        $this->assertSame(PostStatus::Terbit, $post->fresh()->status);
        $this->assertStringContainsString('0123-01-000555-30-1', $post->fresh()->review_note);
    }

    public function test_halaman_dan_pengumuman_menolak_nomor_rekening_tak_terdaftar(): void
    {
        $this->actingAs(User::where('role', 'admin')->first());
        Filament::setCurrentPanel('admin');
        Livewire::test(\App\Filament\Pages\SiteSettings::class)
            ->fillForm(['notice_active' => true, 'notice_text' => 'Infak ke BSI 711 222 3334'])
            ->call('save')->assertHasFormErrors(['notice_text']);
        Livewire::test(\App\Filament\Pages\SiteSettings::class)
            ->fillForm(['notice_active' => true, 'notice_text' => 'Infak lewat halaman /infak, konfirmasi 0811-500-822'])
            ->call('save')->assertHasNoFormErrors();
    }
}

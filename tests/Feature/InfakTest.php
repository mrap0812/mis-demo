<?php

namespace Tests\Feature;

use App\Models\DonationAccount;
use App\Models\DonationAccountChange;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rekening & QRIS: persetujuan ganda (maker-checker). */
class InfakTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function rekening(array $over = []): array
    {
        return $over + ['kind' => 'rekening', 'purpose' => 'Infak pembangunan', 'bank_name' => 'BSI', 'account_number' => '711 222 3334', 'account_holder' => 'DKM Uji'];
    }

    public function test_rekening_dari_poster_belum_tayang_sebelum_disetujui(): void
    {
        $this->assertSame(3, DonationAccountChange::where('status', 'menunggu')->count()); // operasional + 2 rekening kampanye
        $this->assertSame(0, DonationAccount::count());
        $this->get('/infak')->assertOk()->assertSee('Data rekening sedang diverifikasi')->assertDontSee('400 4000 949')->assertSee('Waspada penipuan');
    }

    public function test_pengaju_tidak_bisa_menyetujui_sendiri_orang_kedua_bisa(): void
    {
        $bendahara = $this->user('bendahara');
        $admin = $this->user('admin');
        $c = DonationAccountChange::propose($bendahara, 'create', null, $this->rekening(), 'Uji');
        $this->get('/infak')->assertDontSee('711 222 3334');

        $this->assertFalse($bendahara->can('approve', $c));
        try {
            $c->approve($bendahara, '7112223334');
            $this->fail('Pengaju berhasil menyetujui sendiri');
        } catch (DomainException) {
        }

        $this->assertTrue($admin->can('approve', $c));
        $acc = $c->approve($admin, '711-222-3334');
        $this->assertTrue($acc->is_active);
        $this->assertSame($admin->id, $acc->approved_by);
        $this->get('/infak')->assertSee('711 222 3334')->assertSee('Diverifikasi');
    }

    public function test_konfirmasi_nomor_yang_salah_ditolak(): void
    {
        $c = DonationAccountChange::propose($this->user('bendahara'), 'create', null, $this->rekening(), 'Uji');
        $this->expectException(DomainException::class);
        $c->approve($this->user('admin'), '7112223335');
    }

    public function test_perubahan_tidak_mengubah_data_publik_sebelum_disetujui(): void
    {
        $admin = $this->user('admin');
        $bend = $this->user('bendahara');
        $acc = DonationAccountChange::propose($bend, 'create', null, $this->rekening(), 'Uji')->approve($admin, '7112223334');

        $c = DonationAccountChange::propose($admin, 'update', $acc, ['account_number' => '999 888 7776', 'purpose' => 'Infak pembangunan'], 'Uji ganti');
        $this->assertSame(['account_number' => '999 888 7776'], $c->payload, 'Hanya kolom yang berubah yang disimpan');
        $this->get('/infak')->assertSee('711 222 3334')->assertDontSee('999 888 7776');

        $this->expectException(DomainException::class); // satu pengajuan menunggu per rekening
        DonationAccountChange::propose($bend, 'retire', $acc, [], 'dobel');
    }

    public function test_tolak_dan_nonaktifkan(): void
    {
        $admin = $this->user('admin');
        $bend = $this->user('bendahara');
        $acc = DonationAccountChange::propose($bend, 'create', null, $this->rekening(), 'Uji')->approve($admin, '7112223334');

        $u = DonationAccountChange::propose($admin, 'update', $acc, ['account_number' => '1234567'], 'Uji');
        $u->reject($bend, 'Tidak sesuai buku rekening');
        $this->assertSame('711 222 3334', $acc->fresh()->account_number);

        $r = DonationAccountChange::propose($bend, 'retire', $acc, [], 'Rekening ditutup');
        $r->approve($admin, 'nonaktifkan');
        $this->assertFalse($acc->fresh()->is_active);
        $this->get('/infak')->assertDontSee('711 222 3334');
    }

    public function test_qris_wajib_dicentang_sudah_dipindai(): void
    {
        $c = DonationAccountChange::propose($this->user('bendahara'), 'create', null,
            ['kind' => 'qris', 'purpose' => 'Infak QRIS', 'account_holder' => 'DKM', 'qris_image' => 'qris/uji.png', 'qris_merchant' => 'MASJID UJI'], 'Uji');
        $this->assertTrue($c->needsQrisCheck());
        try {
            $c->approve($this->user('admin'), null, false);
            $this->fail('QRIS disetujui tanpa dipindai');
        } catch (DomainException) {
        }
        $c->approve($this->user('admin'), null, true);
        $this->get('/infak')->assertSee('MASJID UJI');
    }

    public function test_editor_dan_peninjau_tidak_bisa_masuk_menu_infak(): void
    {
        foreach (['editor', 'peninjau'] as $role) {
            $this->actingAs($this->user($role));
            $this->get('/admin/rekening-infak')->assertForbidden();
            $this->get('/admin/persetujuan-infak')->assertForbidden();
            $this->flushSession();
        }
        $this->expectException(DomainException::class);
        DonationAccountChange::propose($this->user('editor'), 'create', null, $this->rekening(), 'x');
    }

    public function test_bendahara_bisa_membuka_menu_dan_log_tercatat(): void
    {
        $this->actingAs($this->user('bendahara'));
        $this->get('/admin/rekening-infak')->assertOk();
        $this->get('/admin/persetujuan-infak')->assertOk()->assertSee('Infak operasional masjid');
        $c = DonationAccountChange::first();
        $c->approve($this->user('admin'), '4004000949');
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'DonationAccount', 'action' => 'created']);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'DonationAccountChange', 'action' => 'updated']);
    }
}

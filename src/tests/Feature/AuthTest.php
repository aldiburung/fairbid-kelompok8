<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MembuatDataFairBid;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use MembuatDataFairBid;
    use RefreshDatabase;

    public function test_registrasi_personal_menyimpan_user_profil_dan_verifikasi_dengan_sandi_terhash(): void
    {
        Storage::fake('public');
        $sandi = 'SandiRahasia123';

        $response = $this->post(route('register.submit'), $this->dataRegistrasi(
            'pengguna@example.test',
            $sandi,
        ));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('user', [
            'email' => 'pengguna@example.test',
            'tipe_akun' => 'PERSONAL',
            'status_verifikasi' => 'MENUNGGU',
        ]);
        $user = \App\Models\User::query()->where('email', 'pengguna@example.test')->firstOrFail();
        $this->assertDatabaseHas('personal_profile', [
            'user_id' => $user->user_id,
            'nama_lengkap' => 'Peserta FairBid',
        ]);
        $this->assertDatabaseHas('verification', [
            'user_id' => $user->user_id,
            'jenis_dokumen' => 'KTP',
            'status' => 'MENUNGGU',
        ]);
        $this->assertNotSame($sandi, $user->password_hash);
        $this->assertTrue(Hash::check($sandi, $user->password_hash));
    }

    public function test_registrasi_menolak_email_yang_sudah_digunakan(): void
    {
        Storage::fake('public');
        $this->buatUser(['email' => 'terdaftar@example.test']);

        $this->post(route('register.submit'), $this->dataRegistrasi('terdaftar@example.test'))
            ->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);
    }

    public function test_registrasi_menolak_kata_sandi_kurang_dari_delapan_karakter(): void
    {
        Storage::fake('public');

        $this->post(route('register.submit'), $this->dataRegistrasi(
            'pendek@example.test',
            'abc123',
        ))->assertSessionHasErrors(['password' => 'Kata sandi minimal 8 karakter.']);
    }

    public function test_login_salah_menampilkan_pesan_kesalahan(): void
    {
        $this->buatUser([
            'email' => 'login@example.test',
            'password' => 'SandiBenar123',
        ]);

        $this->post(route('login.submit'), [
            'email' => 'login@example.test',
            'password' => 'SandiSalah123',
        ])->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    }

    private function dataRegistrasi(string $email, string $password = 'SandiRahasia123'): array
    {
        return [
            'tipe_akun' => 'PERSONAL',
            'email' => $email,
            'no_hp' => '081234567890',
            'password' => $password,
            'password_confirmation' => $password,
            'syarat' => '1',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'),
            'nama_lengkap' => 'Peserta FairBid',
            'nik' => '3171234567890123',
            'tgl_lahir' => '1990-01-01',
            'alamat' => 'Jl. Pengujian No. 1',
        ];
    }
}

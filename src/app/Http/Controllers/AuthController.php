<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipe_akun' => ['required', Rule::in(['PERSONAL', 'BISNIS'])],
            'email' => ['required', 'email', 'max:255', Rule::unique('user', 'email')],
            'no_hp' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'syarat' => ['accepted'],
            'foto_ktp' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'nama_lengkap' => ['exclude_unless:tipe_akun,PERSONAL', 'required', 'string', 'max:255'],
            'nik' => ['exclude_unless:tipe_akun,PERSONAL', 'required', 'digits:16', Rule::unique('personal_profile', 'nik')],
            'tgl_lahir' => ['exclude_unless:tipe_akun,PERSONAL', 'required', 'date'],
            'alamat' => ['required', 'string'],
            'nama_usaha' => ['exclude_unless:tipe_akun,BISNIS', 'required', 'string', 'max:255'],
            'nib' => ['exclude_unless:tipe_akun,BISNIS', 'required', 'string', Rule::unique('business_profile', 'nib')],
            'npwp' => ['exclude_unless:tipe_akun,BISNIS', 'required', 'string', Rule::unique('business_profile', 'npwp')],
            'nama_penanggung_jawab' => ['exclude_unless:tipe_akun,BISNIS', 'required', 'string', 'max:255'],
        ], [
            'tipe_akun.required' => 'Tipe akun wajib dipilih.',
            'tipe_akun.in' => 'Tipe akun tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah terdaftar.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus terdiri dari 10 sampai 15 digit angka.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.string' => 'Kata sandi harus berupa teks.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'syarat.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
            'foto_ktp.required' => 'Foto KTP wajib diunggah.',
            'foto_ktp.image' => 'File yang diunggah harus berupa gambar.',
            'foto_ktp.mimes' => 'Foto KTP harus berformat JPG atau PNG.',
            'foto_ktp.max' => 'Ukuran foto KTP maksimal 2 MB.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 255 karakter.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'tgl_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tgl_lahir.date' => 'Format tanggal lahir tidak valid.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'nama_usaha.required' => 'Nama usaha wajib diisi.',
            'nama_usaha.max' => 'Nama usaha maksimal 255 karakter.',
            'nib.required' => 'NIB wajib diisi.',
            'nib.unique' => 'NIB sudah terdaftar.',
            'npwp.required' => 'NPWP wajib diisi.',
            'npwp.unique' => 'NPWP sudah terdaftar.',
            'nama_penanggung_jawab.required' => 'Nama penanggung jawab wajib diisi.',
            'nama_penanggung_jawab.max' => 'Nama penanggung jawab maksimal 255 karakter.',
        ]);

        $filePath = null;

        try {
            $user = DB::transaction(function () use ($request, $validated, &$filePath): User {
                $user = User::create([
                    'email' => $validated['email'],
                    'password_hash' => Hash::make($validated['password']),
                    'no_hp' => $validated['no_hp'],
                    'tipe_akun' => $validated['tipe_akun'],
                    'peran' => 'USER',
                    'status_verifikasi' => 'MENUNGGU',
                    'status_akun' => 'AKTIF',
                ]);

                if ($validated['tipe_akun'] === 'PERSONAL') {
                    $user->personalProfile()->create([
                        'nama_lengkap' => $validated['nama_lengkap'],
                        'nik' => $validated['nik'],
                        'tgl_lahir' => $validated['tgl_lahir'],
                        'alamat' => $validated['alamat'],
                    ]);
                } else {
                    $user->businessProfile()->create([
                        'nama_usaha' => $validated['nama_usaha'],
                        'nib' => $validated['nib'],
                        'npwp' => $validated['npwp'],
                        'alamat' => $validated['alamat'],
                        'nama_penanggung_jawab' => $validated['nama_penanggung_jawab'],
                    ]);
                }

                $storedPath = $request->file('foto_ktp')->store('verifications', 'public');

                if ($storedPath === false) {
                    throw new RuntimeException('Foto KTP gagal disimpan.');
                }

                $filePath = $storedPath;

                DB::table('verification')->insert([
                    'user_id' => $user->user_id,
                    'jenis_dokumen' => 'KTP',
                    'file_url' => $filePath,
                    'status' => 'MENUNGGU',
                    'submitted_at' => now(),
                ]);

                return $user;
            });
        } catch (Throwable $exception) {
            if ($filePath !== null) {
                Storage::disk('public')->delete($filePath);
            }

            throw $exception;
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'Pendaftaran berhasil. Dokumen Anda sedang diverifikasi.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi salah.'])
                ->onlyInput('email');
        }

        if (Auth::user()->status_akun !== 'AKTIF') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load(['personalProfile', 'businessProfile']);
        $latestVerification = Verification::query()
            ->where('user_id', $user->user_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('verification_id')
            ->first();

        return view('account.profil', compact('user', 'latestVerification'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'no_hp' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'alamat' => ['required', 'string'],
        ], [
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Nomor HP harus terdiri dari 10 sampai 15 digit angka.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string' => 'Alamat harus berupa teks.',
        ]);

        $user = $request->user()->load(['personalProfile', 'businessProfile']);
        $profile = $user->tipe_akun === 'PERSONAL'
            ? $user->personalProfile
            : $user->businessProfile;

        abort_if($profile === null, 404);

        DB::transaction(function () use ($user, $profile, $validated): void {
            $user->update(['no_hp' => $validated['no_hp']]);
            $profile->update(['alamat' => $validated['alamat']]);
        });

        return redirect()->route('account.profile')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    public function resubmitForm(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->status_verifikasi, ['BELUM', 'DITOLAK'], true), 403);

        $latestVerification = Verification::query()
            ->where('user_id', $user->user_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('verification_id')
            ->first();

        return view('account.verifikasi', compact('latestVerification'));
    }

    public function resubmit(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->status_verifikasi, ['BELUM', 'DITOLAK'], true), 403);

        $validated = $request->validate([
            'foto_ktp' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'foto_ktp.required' => 'Foto KTP wajib diunggah.',
            'foto_ktp.image' => 'File yang diunggah harus berupa gambar.',
            'foto_ktp.mimes' => 'Foto KTP harus berformat JPG atau PNG.',
            'foto_ktp.max' => 'Ukuran foto KTP maksimal 2 MB.',
        ]);

        $filePath = null;

        try {
            DB::transaction(function () use ($request, $user, &$filePath): void {
                $storedPath = $request->file('foto_ktp')->store('verifications', 'public');

                if ($storedPath === false) {
                    throw new RuntimeException('Foto KTP gagal disimpan.');
                }

                $filePath = $storedPath;

                Verification::create([
                    'user_id' => $user->user_id,
                    'jenis_dokumen' => 'KTP',
                    'file_url' => $filePath,
                    'status' => 'MENUNGGU',
                    'submitted_at' => now(),
                ]);

                $user->update(['status_verifikasi' => 'MENUNGGU']);
            });
        } catch (Throwable $exception) {
            if ($filePath !== null) {
                Storage::disk('public')->delete($filePath);
            }

            throw $exception;
        }

        return redirect()->route('account.profile')
            ->with('success', 'Dokumen diajukan ulang dan sedang diverifikasi.');
    }
}

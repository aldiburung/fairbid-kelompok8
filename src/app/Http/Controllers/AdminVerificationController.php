<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminVerificationController extends Controller
{
    public function index(): View
    {
        $verifications = Verification::with('user:user_id,email,tipe_akun')
            ->where('status', 'MENUNGGU')
            ->orderBy('submitted_at')
            ->get();

        return view('admin.verifikasi', compact('verifications'));
    }

    public function approve(Verification $verification): RedirectResponse
    {
        DB::transaction(function () use ($verification): void {
            $verification->update([
                'status' => 'DISETUJUI',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $verification->user()->update([
                'status_verifikasi' => 'DISETUJUI',
            ]);
        });

        return redirect()->route('admin.verifikasi.index')
            ->with('success', 'Verifikasi disetujui.');
    }

    public function reject(Request $request, Verification $verification): RedirectResponse
    {
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($verification, $validated): void {
            $verification->update([
                'status' => 'DITOLAK',
                'alasan_penolakan' => $validated['alasan_penolakan'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $verification->user()->update([
                'status_verifikasi' => 'DITOLAK',
            ]);
        });

        return redirect()->route('admin.verifikasi.index')
            ->with('success', 'Verifikasi ditolak.');
    }
}

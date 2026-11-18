<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use App\Services\AuditLogService;
use App\Services\NotificationService;
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

    public function approve(
        Request $request,
        Verification $verification,
        NotificationService $notificationService,
        AuditLogService $auditLogService,
    ): RedirectResponse
    {
        DB::transaction(function () use ($request, $verification, $auditLogService): void {
            $oldStatus = $verification->status;
            $verification->update([
                'status' => 'DISETUJUI',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $verification->user()->update([
                'status_verifikasi' => 'DISETUJUI',
            ]);

            $auditLogService->record(
                (int) $request->user()->user_id,
                'VERIFIKASI_DISETUJUI',
                'verification',
                (int) $verification->verification_id,
                ['status' => $oldStatus],
                ['status' => 'DISETUJUI'],
            );
        });

        $notificationService->send(
            (int) $verification->user_id,
            'VERIFIKASI',
            'Verifikasi disetujui.',
        );

        return redirect()->route('admin.verifikasi.index')
            ->with('success', 'Verifikasi disetujui.');
    }

    public function reject(
        Request $request,
        Verification $verification,
        NotificationService $notificationService,
        AuditLogService $auditLogService,
    ): RedirectResponse
    {
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($request, $verification, $validated, $auditLogService): void {
            $oldStatus = $verification->status;
            $verification->update([
                'status' => 'DITOLAK',
                'alasan_penolakan' => $validated['alasan_penolakan'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $verification->user()->update([
                'status_verifikasi' => 'DITOLAK',
            ]);

            $auditLogService->record(
                (int) $request->user()->user_id,
                'VERIFIKASI_DITOLAK',
                'verification',
                (int) $verification->verification_id,
                ['status' => $oldStatus],
                ['status' => 'DITOLAK'],
            );
        });

        $notificationService->send(
            (int) $verification->user_id,
            'VERIFIKASI',
            'Verifikasi ditolak: ' . $validated['alasan_penolakan'] . '.',
        );

        return redirect()->route('admin.verifikasi.index')
            ->with('success', 'Verifikasi ditolak.');
    }
}

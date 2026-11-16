<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const INVALID_LINK_MESSAGE = 'Tautan pemulihan tidak valid atau sudah kedaluwarsa.';

    public function showRequest(): View
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user !== null) {
            $token = Str::random(64);

            PasswordResetToken::create([
                'user_id' => $user->user_id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(60),
            ]);

            $resetUrl = route('password.reset', ['token' => $token]);

            Mail::raw(
                "Untuk mengatur ulang kata sandi FairBid, buka tautan berikut:\n\n{$resetUrl}\n\nTautan ini berlaku selama 60 menit.",
                static function ($message) use ($user): void {
                    $message->to($user->email)
                        ->subject('Tautan pemulihan kata sandi FairBid');
                },
            );
        }

        return back()->with('success', 'Jika email terdaftar, tautan pemulihan telah dikirim.');
    }

    public function showReset(string $token): View
    {
        return view('auth.reset', compact('token'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $wasReset = DB::transaction(function () use ($validated): bool {
            $resetToken = PasswordResetToken::query()
                ->where('token_hash', hash('sha256', $validated['token']))
                ->lockForUpdate()
                ->first();

            if (
                $resetToken === null
                || $resetToken->used_at !== null
                || $resetToken->expires_at->isPast()
            ) {
                return false;
            }

            $user = User::query()
                ->where('user_id', $resetToken->user_id)
                ->where('email', $validated['email'])
                ->first();

            if ($user === null) {
                return false;
            }

            $user->update([
                'password_hash' => Hash::make($validated['password']),
            ]);
            $resetToken->update(['used_at' => now()]);

            return true;
        });

        if (! $wasReset) {
            return back()
                ->withErrors(['token' => self::INVALID_LINK_MESSAGE])
                ->withInput($request->only('email', 'token'));
        }

        return redirect()->route('login')
            ->with('success', 'Kata sandi berhasil diubah.');
    }
}

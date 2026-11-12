<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function send(int $userId, string $tipe, string $isi, ?int $auctionId = null): Notification
    {
        return Notification::query()->create([
            'user_id' => $userId,
            'auction_id' => $auctionId,
            'tipe' => $tipe,
            'kanal' => 'IN_APP',
            'isi' => $isi,
            'status_kirim' => 'TERKIRIM',
        ]);
    }

    public function sendOnce(int $userId, string $tipe, string $isi, ?int $auctionId = null): ?Notification
    {
        $existing = Notification::query()
            ->where('user_id', $userId)
            ->where('tipe', $tipe)
            ->where('auction_id', $auctionId)
            ->where('isi', $isi)
            ->first();

        return $existing ?? $this->send($userId, $tipe, $isi, $auctionId);
    }
}

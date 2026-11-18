<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public function record(
        int $actorId,
        string $aksi,
        string $entityType,
        int $entityId,
        ?array $lama = null,
        ?array $baru = null,
    ): void {
        AuditLog::query()->create([
            'actor_id' => $actorId,
            'aksi' => $aksi,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'nilai_lama' => $this->withoutSecrets($lama),
            'nilai_baru' => $this->withoutSecrets($baru),
        ]);
    }

    private function withoutSecrets(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $safeValues = [];

        foreach ($values as $key => $value) {
            if (preg_match('/password|sandi|token/i', (string) $key) === 1) {
                continue;
            }

            $safeValues[$key] = is_array($value)
                ? $this->withoutSecrets($value)
                : $value;
        }

        return $safeValues;
    }
}

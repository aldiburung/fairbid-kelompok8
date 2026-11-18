<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'aksi' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['nullable', 'string', 'max:50'],
        ]);

        $auditLogs = AuditLog::query()
            ->with('actor:user_id,email')
            ->when($filters['aksi'] ?? null, fn ($query, $aksi) => $query->where('aksi', $aksi))
            ->when(
                $filters['entity_type'] ?? null,
                fn ($query, $entityType) => $query->where('entity_type', $entityType),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('log_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit-log', compact('auditLogs'));
    }
}

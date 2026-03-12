<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->search, fn ($q, $search) => $q->where('action', 'like', "%{$search}%")
                ->orWhere('auditable_type', 'like', "%{$search}%"))
            ->when($request->user_id, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($request->action, fn ($q, $action) => $q->where('action', $action))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        // Get stats for all events, not just current page
        $stats = [
            'total' => AuditLog::count(),
            'created' => AuditLog::where('action', 'created')->count(),
            'updated' => AuditLog::where('action', 'updated')->count(),
            'deleted' => AuditLog::where('action', 'deleted')->count(),
        ];

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only('search', 'user_id', 'action', 'date_from', 'date_to'),
        ]);
    }
}

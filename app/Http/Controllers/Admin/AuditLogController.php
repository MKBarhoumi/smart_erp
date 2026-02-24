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
            ->when($request->search, fn ($q, $search) => $q->where('event', 'like', "%{$search}%")
                ->orWhere('auditable_type', 'like', "%{$search}%"))
            ->when($request->user_id, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($request->event, fn ($q, $event) => $q->where('event', $event))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'filters' => $request->only('search', 'user_id', 'event', 'date_from', 'date_to'),
        ]);
    }
}

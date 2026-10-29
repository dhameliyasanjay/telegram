<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    /**
     * Display searchable administrative activity audit logs.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = AdminActivityLog::with('user');

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('action', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term)
                  ->orWhere('admin_name', 'LIKE', $term)
                  ->orWhere('ip_address', 'LIKE', $term);
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->latest('id')->paginate(25)->withQueryString();
        $distinctActions = AdminActivityLog::distinct()->pluck('action');

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return view('admin.activity_logs.index', [
            'logs'            => $logs,
            'distinctActions' => $distinctActions,
            'filters'         => $request->only(['search', 'action']),
        ]);
    }
}


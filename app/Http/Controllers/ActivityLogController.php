<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->string('module'));
        }

        if ($request->filled('search')) {
            $keyword = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('user_name', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword)
                    ->orWhere('route_name', 'like', $keyword)
                    ->orWhere('ip_address', 'like', $keyword);
            });
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('created_at', '>=', $request->date('dari_tanggal'));
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('created_at', '<=', $request->date('sampai_tanggal'));
        }

        $logs = $query->limit(1000)->get();

        $modules = ActivityLog::query()->distinct()->orderBy('module')->pluck('module');

        return view('activity-logs.index', compact('logs', 'modules'));
    }
}

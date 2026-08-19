<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'user_id', 'action', 'date_from', 'date_to']);

        return view('admin.logs.index', [
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'logs' => AdminActionLog::query()
                ->with('user')
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $search = '%' . $request->string('q')->toString() . '%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('action', 'like', $search)
                            ->orWhere('model_type', 'like', $search)
                            ->orWhere('ip_address', 'like', $search)
                            ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search));
                    });
                })
                ->when($request->integer('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
                ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
                ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
                ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
                ->latest()
                ->paginate(50)
                ->withQueryString(),
            'actions' => AdminActionLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }
}

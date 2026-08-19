<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(): View
    {
        return view('admin.logs.index', [
            'logs' => AdminActionLog::query()
                ->with('user')
                ->latest()
                ->paginate(50),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Video;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
            abort(403, 'Unauthorized');
        }

        $videoCount = \App\Models\Video::count();
        $publishedCount = \App\Models\Video::where('is_published', true)->count();
        $hiddenCount = \App\Models\Video::where('is_published', false)->count();
        $adminCount = \App\Models\User::where('role', 'admin')->count();
        $recentLogs = \Spatie\Activitylog\Models\Activity::latest()->take(10)->get();

        return view('admin.dashboard', compact(
            'videoCount',
            'publishedCount',
            'hiddenCount',
            'adminCount',
            'recentLogs'
        ));
    }
}

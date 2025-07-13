<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Models\Video;
use App\Exports\ActivityLogsExport;
use Maatwebsite\Excel\Facades\Excel;

//exporting to excel
Route::get('/admin/activity-logs/export', function () {
    return Excel::download(new ActivityLogsExport, 'activity_logs.xlsx');
})->name('admin.logs.export')->middleware('auth');

Route::post('/videos/reorder', [VideoController::class, 'reorder'])->name('videos.reorder');

// Landing Page
Route::get('/', function () {
    $latest = Video::where('is_published', true)->latest()->first();
    $others = Video::where('is_published', true)
        ->where('id', '!=', optional($latest)->id)
        ->latest()
        ->get();

    return view('landing', compact('latest', 'others'));
})->name('landing');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard (User)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['verified'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Password Change
    Route::get('/account/change-password', [AccountController::class, 'editPassword'])->name('account.password.edit');
    Route::post('/account/change-password', [AccountController::class, 'updatePassword'])->name('account.password.update');

    // Video Routes
    Route::resource('videos', VideoController::class);
    Route::post('/videos/{id}/toggle-publish', [VideoController::class, 'togglePublish'])->name('videos.togglePublish');
});

// Admin Dashboard + Logs (Only for logged in users with admin middleware)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'superadmin'])) {
            abort(403, 'Unauthorized');
        }
        return app(\App\Http\Controllers\DashboardController::class)->index();
    })->name('dashboard');

    Route::get('/activity-logs', [ActivityLogController::class, 'showLogs'])->name('logs.index');
});

// Admin users (restricted to admin + superadmin)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::group(['middleware' => function ($request, $next) {
            if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'superadmin'])) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        }], function () {
            // Exclude 'update' from resource and define it with POST
            Route::resource('users', AdminUserController::class)->except(['update']);
            Route::post('users/{user}/update', [AdminUserController::class, 'update'])->name('users.update');
        });
    });
});

// Auth routes (Laravel Breeze / Jetstream / Fortify / UI)
require __DIR__ . '/auth.php';

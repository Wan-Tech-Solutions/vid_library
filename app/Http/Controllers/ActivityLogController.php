<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Response;
use PDF; // For PDF export (use Barryvdh\DomPDF)
use Maatwebsite\Excel\Facades\Excel; // For CSV (Laravel Excel)
class ActivityLogController extends Controller
{
    public function showLogs(Request $request)
    {
        $query = Activity::query()->latest();

        // Filters
        if ($request->filled('user')) {
            $query->whereHas('causer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user . '%');
            });
        }

        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Export CSV
        if ($request->export === 'csv') {
            $logs = $query->get();
            $csv = $logs->map(function ($log) {
                return [
                    'Date' => $log->created_at,
                    'Description' => $log->description,
                    'User' => $log->causer?->name ?? 'System',
                    'Properties' => json_encode($log->properties),
                ];
            });

            $filename = 'activity_logs_' . now()->format('Ymd_His') . '.csv';
            $headers = ['Content-Type' => 'text/csv'];

            $callback = function () use ($csv) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_keys($csv[0] ?? []));
                foreach ($csv as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers)->withHeaders([
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        // Export PDF
        if ($request->export === 'pdf') {
            $logs = $query->get();
            $pdf = PDF::loadView('admin.logs.pdf', compact('logs'));
            return $pdf->download('activity_logs_' . now()->format('Ymd_His') . '.pdf');
        }

        // Regular paginated logs
        $logs = $query->paginate(20);
        return view('admin.logs.index', compact('logs'));
    }
}

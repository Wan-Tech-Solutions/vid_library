<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActivityLogsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return \Spatie\Activitylog\Models\Activity::latest()->get()->map(function ($log) {
            $properties = is_array($log->properties) || is_object($log->properties)
                ? json_encode($log->properties)
                : $log->properties;

            return [
                'Date' => $log->created_at->format('Y-m-d H:i'),
                'Description' => $log->description,
                'User' => $log->causer?->name ?? 'System',
                'Properties' => $properties,
            ];
        });
    }

    public function headings(): array
    {
        return ['Date', 'Description', 'User', 'Properties'];
    }
}

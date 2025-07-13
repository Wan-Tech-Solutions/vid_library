<!DOCTYPE html>
<html>

<head>
    <title>Activity Logs</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        th {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <h2>Activity Logs</h2>
    <table>
        <thead>

            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>User</th>
                <th>Properties</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->causer?->name ?? 'System' }}</td>
                    <td>
                        <pre>{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>

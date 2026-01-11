<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            font-size: 8px;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        @if($barangay)
            <p>Barangay: {{ $barangay->name }}</p>
        @endif
        <p>Generated on: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Program Name</th>
                <th>Barangay</th>
                <th>Category</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Start Time</th>
                <th>End Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($programs as $program)
                <tr>
                    <td>{{ $program->name ?? 'N/A' }}</td>
                    <td>{{ $program->barangay->name ?? 'N/A' }}</td>
                    <td>{{ $program->category->name ?? 'N/A' }}</td>
                    <td>{{ $program->program_start_date ? \Carbon\Carbon::parse($program->program_start_date)->format('M d, Y') : 'N/A' }}</td>
                    <td>{{ $program->program_end_date ? \Carbon\Carbon::parse($program->program_end_date)->format('M d, Y') : 'N/A' }}</td>
                    <td>{{ $program->program_start_time ? \Carbon\Carbon::parse($program->program_start_time)->format('g:i A') : 'N/A' }}</td>
                    <td>{{ $program->program_end_time ? \Carbon\Carbon::parse($program->program_end_time)->format('g:i A') : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total Records: {{ $programs->count() }}</p>
    </div>
</body>
</html>

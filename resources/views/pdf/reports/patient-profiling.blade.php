<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resident Profiling Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
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
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'province' => $province ?? 'DAVAO DEL NORTE',
        'municipality' => $municipality ?? 'CARMEN',
        'reportTitle' => $reportTitle ?? 'Resident Profiling Report',
        'barangayName' => $barangayName ?? 'ALL BARANGAYS',
        'dateTime' => $dateTime ?? $date ?? now()->format('F d, Y h:i A'),
    ])

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $index => $cell)
                        <td>@php
                            $header = $headers[$index] ?? '';
                            if ($header === 'Contact Number' && $cell && is_numeric($cell)) {
                                $num = preg_replace('/\D/', '', (string)$cell);
                                if (strlen($num) >= 10) {
                                    echo substr($num, 0, 2) . ' ' . substr($num, 2, 3) . ' ' . substr($num, 5, 3) . ' ' . substr($num, 8);
                                } else {
                                    echo $cell;
                                }
                            } else {
                                echo $cell ?? 'N/A';
                            }
                        @endphp</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.footer', [
        'totalRecords' => count($rows ?? []),
        'barangayName' => $barangayName ?? 'ALL BARANGAYS',
    ])
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Referrals Report</title>
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
        'reportTitle' => $reportTitle ?? 'Referrals Report',
        'barangayName' => $barangayName ?? 'ALL BARANGAYS',
        'dateTime' => $dateTime ?? $date ?? now()->format('F d, Y h:i A'),
    ])

    <table>
        <thead>
            <tr>
                <th>Reference ID</th>
                <th>Patient Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Purok</th>
                <th>Barangay</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Referred To</th>
                <th>Referred By</th>
                <th>Date Referred</th>
            </tr>
        </thead>
        <tbody>
            @foreach($referrals as $referral)
                @php
                    $patient = $referral->patient ?? $referral->consultation?->patient;
                @endphp
                <tr>
                    <td>{{ $referral->id }}</td>
                    <td>{{ $patient ? ($patient->first_name . ' ' . $patient->last_name) : 'N/A' }}</td>
                    <td>{{ $patient?->age ?? 'N/A' }}</td>
                    <td>{{ $patient?->sex ?? 'N/A' }}</td>
                    <td>{{ $patient?->purok?->name ?? 'N/A' }}</td>
                    <td>{{ $patient?->barangay?->name ?? 'N/A' }}</td>
                    <td>{{ $referral->urgency }}</td>
                    <td>{{ $referral->status }}</td>
                    <td>{{ $referral->referred_to }}</td>
                    <td>{{ $referral->user?->name ?? 'N/A' }}</td>
                    <td>{{ $referral->date_referred ? $referral->date_referred->format('M d, Y H:i') : ($referral->created_at->format('M d, Y H:i')) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.footer', [
        'totalRecords' => count($referrals ?? []),
        'barangayName' => $barangayName ?? 'ALL BARANGAYS',
    ])
</body>
</html>

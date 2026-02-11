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
        .header {
            text-align: center;
            margin-bottom: 20px;
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
        <div style="margin-bottom: 10px;">
            <img src="{{ public_path('comm-health-icon.png') }}" alt="Municipality Logo" style="height: 60px; width: auto; display: block; margin: 0 auto;">
        </div>
        <p style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">REPUBLIC OF THE PHILIPPINES</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">PROVINCE OF {{ strtoupper($province ?? 'DAVAO DEL NORTE') }}</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">MUNICIPAL HEALTH OFFICE</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">MUNICIPALITY OF {{ strtoupper($municipality ?? 'CARMEN') }}</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">BARANGAY {{ strtoupper($barangayName ?? 'ALL BARANGAYS') }}</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">{{ strtoupper($reportTitle ?? 'Referrals Report') }}</p>
        <p style="font-size: 10px; margin-bottom: 5px;">&nbsp;</p>
        <p style="font-size: 10px;">As of : {{ $dateTime ?? $date }}</p>
    </div>

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

    <div class="footer">
        <p>Total Records: {{ count($referrals) }}</p>
    </div>
</body>
</html>

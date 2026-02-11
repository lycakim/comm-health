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
        <div style="margin-bottom: 10px;">
            <img src="{{ public_path('comm-health-icon.png') }}" alt="Municipality Logo" style="height: 60px; width: auto; display: block; margin: 0 auto;">
        </div>
        <p style="font-size: 12px; font-weight: bold; margin-bottom: 5px;">REPUBLIC OF THE PHILIPPINES</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">PROVINCE OF {{ strtoupper($province ?? config('app.province', 'DAVAO DEL NORTE')) }}</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">MUNICIPAL HEALTH OFFICE</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">MUNICIPALITY OF {{ strtoupper($municipality ?? config('app.municipality', 'CARMEN')) }}</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">BARANGAY {{ strtoupper($barangayName ?? ($barangay ? $barangay->name : 'ALL BARANGAYS')) }}</p>
        <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">{{ strtoupper($title) }}</p>
        <p style="font-size: 10px; margin-bottom: 5px;">&nbsp;</p>
        <p style="font-size: 10px;">As of : {{ $dateTime ?? $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Birthdate</th>
                <th>Age</th>
                <th>Barangay</th>
                <th>Category</th>
                <th>Blood Pressure</th>
                <th>Sugar Level</th>
                <th>Contact Number</th>
                <th>Gender</th>
                <th>Height</th>
                <th>Weight</th>
                <th>BMI</th>
                <th>Maintenance</th>
                <th>Consultation Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consultations as $consultation)
                @php
                    $patient = $consultation->patient;
                @endphp
                <tr>
                    <td>{{ $patient->first_name ?? '' }} {{ $patient->middle_name ?? '' }} {{ $patient->last_name ?? '' }} {{ $patient->suffix ?? '' }}</td>
                    <td>{{ $patient->birth_date ? $patient->birth_date->format('M d, Y') : 'N/A' }}</td>
                    <td>{{ $patient->age ?? 'N/A' }}</td>
                    <td>{{ $patient->barangay->name ?? 'N/A' }}</td>
                    <td>{{ $patient->category->name ?? 'N/A' }}</td>
                    <td>{{ $patient->blood_pressure ?? 'N/A' }}</td>
                    <td>{{ $patient->sugar_level ?? 'N/A' }}</td>
                    <td>{{ $patient->contact_number ?? 'N/A' }}</td>
                    <td>{{ ucfirst($patient->sex ?? 'N/A') }}</td>
                    <td>{{ $patient->height ?? 'N/A' }}</td>
                    <td>{{ $patient->weight ?? 'N/A' }}</td>
                    <td>{{ $patient->bmi ?? 'N/A' }}</td>
                    <td>{{ is_array($patient->medication_maintenance) ? implode(', ', $patient->medication_maintenance) : ($patient->medication_maintenance ?? 'N/A') }}</td>
                    <td>{{ $consultation->created_at ? $consultation->created_at->format('M d, Y') : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total Records: {{ $consultations->count() }}</p>
    </div>
</body>
</html>

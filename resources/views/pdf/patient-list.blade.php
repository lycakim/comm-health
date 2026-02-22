<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 13px;
        }
        td {
            font-size: 12px;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 13px;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('comm-health-icon.png');
        $logoBase64 = (file_exists($logoPath)) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    @endphp
    <div class="header">
        <div style="margin-bottom: 10px;">
            @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Municipality Logo" style="height: 60px; width: auto; display: block; margin: 0 auto;">
            @endif
        </div>
        <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">REPUBLIC OF THE PHILIPPINES</p>
        <p style="font-size: 15px; font-weight: bold; margin-bottom: 5px;">PROVINCE OF {{ strtoupper($province ?? config('app.province', 'DAVAO DEL NORTE')) }}</p>
        <p style="font-size: 15px; font-weight: bold; margin-bottom: 5px;">MUNICIPAL HEALTH OFFICE</p>
        <p style="font-size: 15px; font-weight: bold; margin-bottom: 5px;">MUNICIPALITY OF {{ strtoupper($municipality ?? config('app.municipality', 'CARMEN')) }}</p>
        <p style="font-size: 15px; font-weight: bold; margin-bottom: 5px;">BARANGAY {{ strtoupper($barangayName ?? ($barangay ? $barangay->name : 'ALL BARANGAYS')) }}</p>
        <p style="font-size: 15px; font-weight: bold; margin-bottom: 5px;">{{ strtoupper($title) }}</p>
        <p style="font-size: 14px; margin-bottom: 5px;">&nbsp;</p>
        <p style="font-size: 14px;">As of : {{ $dateTime ?? $date }}</p>
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
            </tr>
        </thead>
        <tbody>
            @foreach($patients as $patient)
                <tr>
                    <td>{{ $patient->first_name }} {{ $patient->middle_name ?? '' }} {{ $patient->last_name }} {{ $patient->suffix ?? '' }}</td>
                    <td>{{ $patient->birth_date ? $patient->birth_date->format('M d, Y') : 'N/A' }}</td>
                    <td>{{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->age : 'N/A' }}</td>
                    <td>{{ $patient->barangay->name ?? 'N/A' }}</td>
                    <td>{{ $patient->category->name ?? 'N/A' }}</td>
                    <td>{{ $patient->blood_pressure ?? 'N/A' }}</td>
                    <td>{{ $patient->sugar_level ?? 'N/A' }}</td>
                    <td>{{ $patient->contact_number ?? 'N/A' }}</td>
                    <td>{{ ucfirst($patient->sex) ?? 'N/A' }}</td>
                    <td>{{ $patient->height ?? 'N/A' }}</td>
                    <td>{{ $patient->weight ?? 'N/A' }}</td>
                    <td>{{ $patient->bmi ?? 'N/A' }}</td>
                    <td>{{ is_array($patient->medication_maintenance) ? implode(', ', $patient->medication_maintenance) : ($patient->medication_maintenance ?? 'N/A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total Records: {{ $patients->count() }}</p>
    </div>
</body>
</html>




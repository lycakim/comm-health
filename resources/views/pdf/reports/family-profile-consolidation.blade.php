<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Family Profile Consolidation</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; font-size: 9px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .summary-section { display: flex; margin-bottom: 20px; }
        .summary-col { flex: 1; padding: 0 15px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; padding: 2px 0; }
        .summary-label { font-weight: bold; }
        .summary-value { text-align: right; }
        .age-table-wrapper { display: flex; gap: 20px; margin-top: 15px; }
        .age-table { flex: 1; }
        .age-section-title { font-weight: bold; text-align: center; margin-bottom: 10px; font-size: 11px; }
    </style>
</head>
<body>
    @include('pdf.partials.header', [
        'province' => $province ?? 'DAVAO DEL NORTE',
        'municipality' => $municipality ?? 'CARMEN',
        'reportTitle' => $reportTitle ?? 'FAMILY PROFILE CONSOLIDATION',
        'barangayName' => $barangayName ?? 'ALL BARANGAYS',
        'dateTime' => $dateTime ?? now()->format('F d, Y h:i A'),
    ])

    <div class="summary-section">
        <div class="summary-col">
            <div class="summary-row"><span class="summary-label">Total Population:</span><span class="summary-value">{{ number_format($summary['totalPopulation'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total No. of Household Head:</span><span class="summary-value">{{ number_format($summary['householdHeads'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total No. of Houses:</span><span class="summary-value">{{ number_format($summary['totalHouses'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total of Family Head:</span><span class="summary-value">{{ number_format($summary['familyHeads'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total No. of Married:</span><span class="summary-value">{{ number_format($summary['married'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total of Widow:</span><span class="summary-value">{{ number_format($summary['widowMale'] ?? 0) + ($summary['widowFemale'] ?? 0) }}</span></div>
            <div class="summary-row" style="padding-left: 15px;"><span class="summary-label">MALE:</span><span class="summary-value">{{ number_format($summary['widowMale'] ?? 0) }}</span></div>
            <div class="summary-row" style="padding-left: 15px;"><span class="summary-label">FEMALE:</span><span class="summary-value">{{ number_format($summary['widowFemale'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">No. of Live-in Partner:</span><span class="summary-value">{{ number_format($summary['liveIn'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">No. of Solo Parent:</span><span class="summary-value">{{ number_format($summary['soloParent'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">No. of Single MOTHER:</span><span class="summary-value">{{ number_format($summary['singleMother'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">NO. OF SEPARATED:</span><span class="summary-value">{{ number_format($summary['separated'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">No. of Pregnant Women:</span><span class="summary-value">{{ number_format($summary['pregnantWomen'] ?? 0) }}</span></div>
        </div>
        <div class="summary-col">
            <div class="summary-row"><span class="summary-label">Name of RHM:</span><span class="summary-value" style="text-decoration: underline;">_______________</span></div>
            <div class="summary-row"><span class="summary-label">Name of NDP:</span><span class="summary-value" style="text-decoration: underline;">_______________</span></div>
            <div class="summary-row"><span class="summary-label">Date Survey:</span><span class="summary-value" style="text-decoration: underline;">{{ now()->format('Y') }}</span></div>
            <div class="summary-row"><span class="summary-label">Total No. of WRA:</span><span class="summary-value">{{ number_format($summary['wra'] ?? 0) }}</span></div>
            <div class="summary-row" style="padding-left: 15px;"><span class="summary-label">Single WRA:</span><span class="summary-value">{{ number_format($summary['singleWra'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total No. Of Senior Citizens:</span><span class="summary-value">{{ number_format($summary['seniorCitizens'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total No. of SMOKERS Male:</span><span class="summary-value">{{ number_format($summary['smokersMale'] ?? 0) }}</span></div>
            <div class="summary-row" style="padding-left: 15px;"><span class="summary-label">Female:</span><span class="summary-value">{{ number_format($summary['smokersFemale'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">NHTS:CCT:</span><span class="summary-value">{{ number_format($summary['nhtsCct'] ?? 0) }}</span></div>
            <div class="summary-row" style="padding-left: 15px;"><span class="summary-label">Non-CCT:</span><span class="summary-value">{{ number_format($summary['nhtsNonCct'] ?? 0) }}</span></div>
            <div class="summary-row" style="padding-left: 15px;"><span class="summary-label">Set:</span><span class="summary-value">{{ number_format($summary['nhtsSet'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">No. of PWD:</span><span class="summary-value">{{ number_format($summary['pwd'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">No. of OFW's:</span><span class="summary-value">{{ number_format($summary['ofw'] ?? 0) }}</span></div>
            <div class="summary-row"><span class="summary-label">Philhealth:</span><span class="summary-value" style="text-decoration: underline;">_______________</span></div>
            <div class="summary-row"><span class="summary-label">AGR SPONSOR:</span><span class="summary-value" style="text-decoration: underline;">_______________</span></div>
            <div class="summary-row"><span class="summary-label">LGU Sponsor:</span><span class="summary-value" style="text-decoration: underline;">_______________</span></div>
            <div class="summary-row"><span class="summary-label">IP's Sponsor:</span><span class="summary-value" style="text-decoration: underline;">_______________</span></div>
        </div>
    </div>

    <p class="age-section-title">AGE GROUPING:</p>
    <div class="age-table-wrapper">
        <div class="age-table">
            <table>
                <thead><tr><th>Age Group</th><th>MALE</th><th>FEMALE</th><th>TOTAL</th></tr></thead>
                <tbody>
                    @foreach($ageGroupsLeft ?? [] as $row)
                    <tr><td>{{ $row['label'] }}</td><td>{{ $row['male'] }}</td><td>{{ $row['female'] }}</td><td>{{ $row['total'] }}</td></tr>
                    @endforeach
                    <tr style="font-weight: bold;"><td>TOTAL</td><td>{{ $totalLeft['male'] ?? 0 }}</td><td>{{ $totalLeft['female'] ?? 0 }}</td><td>{{ $totalLeft['total'] ?? 0 }}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="age-table">
            <table>
                <thead><tr><th>Age Group</th><th>MALE</th><th>FEMALE</th><th>TOTAL</th></tr></thead>
                <tbody>
                    @foreach($ageGroupsRight ?? [] as $row)
                    <tr><td>{{ $row['label'] }}</td><td>{{ $row['male'] }}</td><td>{{ $row['female'] }}</td><td>{{ $row['total'] }}</td></tr>
                    @endforeach
                    <tr style="font-weight: bold;"><td>TOTAL</td><td>{{ $totalRight['male'] ?? 0 }}</td><td>{{ $totalRight['female'] ?? 0 }}</td><td>{{ $totalRight['total'] ?? 0 }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    @include('pdf.partials.footer', [
        'totalRecords' => $totalRecords ?? ($summary['totalPopulation'] ?? 0),
        'barangayName' => $barangayName ?? 'ALL BARANGAYS',
    ])
</body>
</html>

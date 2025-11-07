<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Non-Communicable Disease Masterlist</title>
    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .sub-header {
            margin-bottom: 10px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 10px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .header-colored {
            background-color: #f9e79f;
        }

        .header-blue {
            background-color: #aed6f1;
        }

        .header-green {
            background-color: #a9dfbf;
        }

        .header-gray {
            background-color: #d6dbdf;
        }

        .header-red {
            background-color: #f5b7b1;
        }

        th.vertical {
            height: 100px;
            vertical-align: top;
            font-size: 9px;
            white-space: nowrap;
            position: relative;
            padding-left: 15px; /* Reserve space for the rotated text width */
            padding-right: 5px;
        }

        th.vertical > div {
            position: absolute;
            /* bottom: 5px; */
            left: 50%;
            text-align: left;
            transform: rotate(-90deg) translateX(-100%);
            transform-origin: left bottom;
            width: max-content;
        }

        td.name-cell {
            text-align: left;
            padding-left: 5px;
        }

        td.remarks-cell {
            border: 2px solid #000;
            font-weight: bold;
            min-width: 60px;
        }

        th.name-col {
            width: 200px;
        }

        th.num-col {
            width: 30px;
        }
    </style>
</head>
<body>

    <div class="title">NON-COMMUNICABLE DISEASE MASTERLIST<br>(SENIOR CITIZENS / PWDs)</div>

    <div class="sub-header">
        <strong>NAME OF BARANGAY:</strong> {{ strtoupper($barangay?->name) ?? 'MAGSAYSAY' }}<br>
        <strong>PUROK:</strong> {{ strtoupper($purok?->name) ?? '1A' }}
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="num-col">#</th>
                <th rowspan="2" class="name-col">NAME</th>
                <th rowspan="2" class="vertical"><div>AGE</div></th>
                <th rowspan="2" class="vertical"><div>GENDER</div></th>
                <th rowspan="2" class="vertical header-colored"><div>PHIC (YES/NO)</div></th>
                <th colspan="4" class="header-blue">DISEASE</th>
                <th colspan="9" class="header-green">MEDICATION</th>
                <th colspan="12" class="header-gray">MONTHLY MONITORING</th>
                <th rowspan="2" class="vertical header-red"><div>REMARKS</div></th>
            </tr>
            <tr>
                <th class="vertical header-blue"><div>NHTPS/IP's</div></th>
                <th class="vertical header-blue"><div>HYPERTENSIVE</div></th>
                <th class="vertical header-blue"><div>DIABETIC</div></th>
                <th class="vertical header-blue"><div>OTHERS</div></th>

                <th class="vertical header-green"><div>Losartan 50mg</div></th>
                <th class="vertical header-green"><div>Amlodipine 5mg</div></th>
                <th class="vertical header-green"><div>Amlodipine 10mg</div></th>
                <th class="vertical header-green"><div>Metoprolol 50mg</div></th>
                <th class="vertical header-green"><div>Metoprolol 100mg</div></th>
                <th class="vertical header-green"><div>Gliclazide 80mg</div></th>
                <th class="vertical header-green"><div>Gliclazide MR 60mg</div></th>
                <th class="vertical header-green"><div>Metformin 500mg</div></th>
                <th class="vertical header-green"><div>Simvastatin 20mg</div></th>

                <th class="vertical header-gray"><div>Jan</div></th>
                <th class="vertical header-gray"><div>Feb</div></th>
                <th class="vertical header-gray"><div>Mar</div></th>
                <th class="vertical header-gray"><div>Apr</div></th>
                <th class="vertical header-gray"><div>May</div></th>
                <th class="vertical header-gray"><div>Jun</div></th>
                <th class="vertical header-gray"><div>Jul</div></th>
                <th class="vertical header-gray"><div>Aug</div></th>
                <th class="vertical header-gray"><div>Sep</div></th>
                <th class="vertical header-gray"><div>Oct</div></th>
                <th class="vertical header-gray"><div>Nov</div></th>
                <th class="vertical header-gray"><div>Dec</div></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records ?? [] as $index => $person)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="name-cell">{{ strtoupper($person->name) }}</td>
                    <td>{{ $person->age }}</td>
                    <td>{{ strtoupper($person->gender[0]) }}</td>
                    <td>{{ $person->phic ? '✓' : '' }}</td>

                    <td>{{ $person->nhtps ? '✓' : '' }}</td>
                    <td>{{ $person->hypertensive ? '✓' : '' }}</td>
                    <td>{{ $person->diabetic ? '✓' : '' }}</td>
                    <td>{{ $person->others ? '✓' : '' }}</td>

                    <td>{{ $person->losartan_50 ? '✓' : '' }}</td>
                    <td>{{ $person->amlodipine_5 ? '✓' : '' }}</td>
                    <td>{{ $person->amlodipine_10 ? '✓' : '' }}</td>
                    <td>{{ $person->metoprolol_50 ? '✓' : '' }}</td>
                    <td>{{ $person->metoprolol_100 ? '✓' : '' }}</td>
                    <td>{{ $person->gliclazide_80 ? '✓' : '' }}</td>
                    <td>{{ $person->gliclazide_mr_60 ? '✓' : '' }}</td>
                    <td>{{ $person->metformin_500 ? '✓' : '' }}</td>
                    <td>{{ $person->simvastatin_20 ? '✓' : '' }}</td>

                    @foreach (['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'] as $month)
                        <td>{{ $person->$month ? '✓' : '' }}</td>
                    @endforeach

                    <td class="remarks-cell">{{ $person->remarks }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="33" style="text-align:center; font-style:italic;">No records available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
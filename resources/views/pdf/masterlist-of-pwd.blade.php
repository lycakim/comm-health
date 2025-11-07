<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Masterlist of Person w/ Disability</title>
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
            font-size: 20px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            word-wrap: break-word;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 5px;
        }

        td {
            font-size: 11px;
        }

        .left {
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .small {
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="title">
        MASTERLIST OF PERSON W/ DISABILITY
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">NO.</th>
                <th style="width: 20%;">NAME<br><span class="small">(Surname, Given, Name, Middle Name)</span></th>
                <th style="width: 4%;">AGE</th>
                <th style="width: 8%;">BIRTHDAY</th>
                <th style="width: 7%;">MARITAL STATUS</th>
                <th style="width: 10%;">ADDRESS</th>
                <th style="width: 6%;">PHIC<br>Y/N</th>
                <th style="width: 8%;">NHTS / 4Ps /<br>NON-NHTS / IPs</th>
                <th style="width: 6%;">BINGE<br>DRINKER</th>
                <th style="width: 6%;">SMOKER</th>
                <th style="width: 6%;">HYPERTENSIVE</th>
                <th style="width: 6%;">DIABETIC</th>
                <th style="width: 8%;">EYE PROBLEM</th>
                <th style="width: 12%;">OTHER DISEASES</th>
                <th style="width: 10%;">BODY PARTS<br>DISABLED</th>
                <th style="width: 10%;">MAINTENANCE</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records ?? [] as $index => $person)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="left">{{ strtoupper($person->name ?? '') }}</td>
                    <td>{{ $person->age ?? '' }}</td>
                    <td>{{ !empty($person->birthday) ? \Carbon\Carbon::parse($person->birthday)->format('m-d-y') : '' }}</td>
                    <td>{{ strtoupper($person->marital_status ?? '') }}</td>
                    <td>{{ strtoupper($person->address ?? '') }}</td>
                    <td>{{ !empty($person->phic) ? '✓' : '' }}</td>
                    <td>{{ strtoupper($person->nhts_category ?? '') }}</td>
                    <td>{{ !empty($person->binge_drinker) ? '✓' : '' }}</td>
                    <td>{{ !empty($person->smoker) ? '✓' : '' }}</td>
                    <td>{{ !empty($person->hypertensive) ? '✓' : '' }}</td>
                    <td>{{ !empty($person->diabetic) ? '✓' : '' }}</td>
                    <td>{{ !empty($person->eye_problem) ? '✓' : '' }}</td>
                    <td class="left">{{ strtoupper($person->other_diseases ?? '') }}</td>
                    <td class="left">{{ strtoupper($person->body_parts_disabled ?? '') }}</td>
                    <td class="left">{{ strtoupper($person->maintenance ?? '') }}</td>
                </tr>
            @empty
                <tr>
                    {{-- Adjust number of <td> to match your column count (16 total here) --}}
                    <td colspan="16" style="text-align:center; font-style:italic;">No records available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
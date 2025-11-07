<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Immunization Masterlist</title>
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
            font-size: 14px;
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
            font-size: 11px;
        }

        td {
            font-size: 11px;
        }

        .left {
            text-align: left;
        }

        .small {
            font-size: 10px;
        }

        .header-sub {
            font-size: 10px;
            background-color: #e9e9e9;
        }
    </style>
</head>
<body>

    <div class="title">
        IMMUNIZATION
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">CCT</th>
                <th style="width: 3%;">NCCT</th>
                <th style="width: 18%;">NAME OF CHILD</th>
                <th style="width: 4%;">WT.</th>
                <th style="width: 4%;">HT.</th>
                <th style="width: 8%;">BIRTHDATE</th>
                <th style="width: 15%;">MOTHER</th>
                <th style="width: 6%;">ADD</th>
                <th style="width: 6%;">BCG</th>

                <!-- PENTA -->
                <th colspan="3" class="header-sub">PENTA</th>

                <!-- POLIO -->
                <th colspan="3" class="header-sub">POLIO</th>

                <!-- IPV -->
                <th colspan="3" class="header-sub">IPV</th>

                <!-- PCV -->
                <th colspan="3" class="header-sub">PCV</th>

                <th style="width: 6%;">MEASLES</th>
                <th style="width: 6%;">MMR</th>
            </tr>
            <tr>
                <th colspan="9"></th>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th colspan="2"></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records ?? [] as $index => $child)
                <tr>
                    <td>{{ $child->cct }}</td>
                    <td>{{ $child->ncct }}</td>
                    <td class="left">{{ strtoupper($child->name) }}</td>
                    <td>{{ $child->weight }}</td>
                    <td>{{ $child->height }}</td>
                    <td>{{ $child->birthdate ? \Carbon\Carbon::parse($child->birthdate)->format('m-d-y') : '' }}</td>
                    <td class="left">{{ strtoupper($child->mother) }}</td>
                    <td>{{ strtoupper($child->address) }}</td>
                    <td>{{ $child->bcg_date ?? '' }}</td>

                    <td>{{ $child->penta_1 ?? '' }}</td>
                    <td>{{ $child->penta_2 ?? '' }}</td>
                    <td>{{ $child->penta_3 ?? '' }}</td>

                    <td>{{ $child->polio_1 ?? '' }}</td>
                    <td>{{ $child->polio_2 ?? '' }}</td>
                    <td>{{ $child->polio_3 ?? '' }}</td>

                    <td>{{ $child->ipv_1 ?? '' }}</td>
                    <td>{{ $child->ipv_2 ?? '' }}</td>
                    <td>{{ $child->ipv_3 ?? '' }}</td>

                    <td>{{ $child->pcv_1 ?? '' }}</td>
                    <td>{{ $child->pcv_2 ?? '' }}</td>
                    <td>{{ $child->pcv_3 ?? '' }}</td>

                    <td>{{ $child->measles ?? '' }}</td>
                    <td>{{ $child->mmr ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    {{-- Adjust number of <td> to match your column count (16 total here) --}}
                    <td colspan="29" style="text-align:center; font-style:italic;">No records available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
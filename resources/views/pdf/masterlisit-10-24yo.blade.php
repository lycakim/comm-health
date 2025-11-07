<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Masterlist (10–24 Y.O.)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        .center {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th, td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            font-weight: bold;
        }

        .no-border {
            border: none !important;
        }

        .footer {
            margin-top: 20px;
            font-size: 11px;
        }

        .footer .col {
            display: inline-block;
            width: 45%;
            vertical-align: top;
        }

        .legend {
            font-size: 10px;
            border: 1px solid #000;
            display: inline-block;
            padding: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="center">
        <h3 style="margin-bottom: 0;">MASTERLIST (10–24 Y.O.)</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>NAME</th>
                <th>ADDRESS</th>
                <th colspan="2">SEX</th>
                <th>AGE</th>
                <th colspan="5">CIVIL STATUS</th>
                <th>TRIBE / ETHNIC ORIGIN</th>
                <th>WITH DISABILITY</th>
                <th>WITH PHILHEALTH</th>
                <th>STUDENT</th>
                <th>OUT-OF-SCHOOL YOUTH</th>
                <th>WORKING</th>
                <th>RELIGION</th>
                <th>NO. OF CHILDREN</th>
                <th>REMARKS</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th>M</th>
                <th>F</th>
                <th></th>
                <th>S</th>
                <th>M</th>
                <th>LI</th>
                <th>SE</th>
                <th>W</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 1; $i <= 20; $i++)
                <tr>
                    <td>{{ $i }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="footer">
        <div>
            Submitted by: ____________________________ <br>
            <small>Brgy. BHW President</small>
        </div>
        <br><br>
        <div>
            Noted: ____________________________ <br>
            <small>City/Municipality BHW President</small>
        </div>
    </div>

    <div style="margin-top: 10px;">
        <div class="legend">
            <b>Civil Status:</b><br>
            S - Single<br>
            M - Married<br>
            LI - Live In<br>
            SE - Separated<br>
            W - Widow
        </div>

        <div class="legend">
            <b>Tribe / Ethnic Origin:</b><br>
            A - Dibabawon<br>
            B - Mansaka<br>
            C - Mandaya<br>
            A - Ata Manobo<br>
            L - Calagan<br>
            F - Others
        </div>

        <div class="legend">
            <b>Student:</b><br>
            A - Elementary<br>
            B - High School<br>
            C - College
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Livestock and Household Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #000;
            text-align: center;
            padding: 3px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 15px;
            text-decoration: underline;
        }
        .no-border td, .no-border th {
            border: none;
        }
        .income td {
            text-align: left;
            padding-left: 10px;
        }
        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
        }
    </style>
</head>
<body>

    <h3 style="text-align:center; text-transform:uppercase;">Barangay Livestock and Household Report</h3>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Birth Cert#</th>
                <th rowspan="2">Marriage #</th>
                <th rowspan="2">Religion</th>
                <th rowspan="2">Tribe</th>
                <th colspan="14">Livestock Commonly Raised in Barangay</th>
            </tr>
            <tr>
                <th>Dog</th>
                <th>Cat</th>
                <th>Chicken</th>
                <th>Roaster</th>
                <th>Duck</th>
                <th>Goat</th>
                <th>Cow</th>
                <th>Carabao</th>
                <th>Horse</th>
                <th>Guinea Pig</th>
                <th>Turkey</th>
                <th>Gansa</th>
                <th>Rabbit</th>
                <th>Pig</th>
                <th>Others</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                <td>{{ $record->birth_cert }}</td>
                <td>{{ $record->marriage_no }}</td>
                <td>{{ $record->religion }}</td>
                <td>{{ $record->tribe }}</td>
                <td>{{ $record->dog }}</td>
                <td>{{ $record->cat }}</td>
                <td>{{ $record->chicken }}</td>
                <td>{{ $record->roaster }}</td>
                <td>{{ $record->duck }}</td>
                <td>{{ $record->goat }}</td>
                <td>{{ $record->cow }}</td>
                <td>{{ $record->carabao }}</td>
                <td>{{ $record->horse }}</td>
                <td>{{ $record->guinea_pig }}</td>
                <td>{{ $record->turkey }}</td>
                <td>{{ $record->gansa }}</td>
                <td>{{ $record->rabbit }}</td>
                <td>{{ $record->pig }}</td>
                <td>{{ $record->others }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Occupation</div>
    <table class="no-border">
        <tr>
            <td>Employed: Private ______ Public ______</td>
            <td>Self-Employed: Business ______</td>
        </tr>
        <tr>
            <td>Farmer ______</td>
            <td>Farm Laborer ______</td>
        </tr>
        <tr>
            <td>Driver ______</td>
            <td>Carpenter ______</td>
        </tr>
        <tr>
            <td>Laborer ______</td>
            <td>Casual ______ Seasonal ______ Contractual ______</td>
        </tr>
    </table>

    <div class="section-title">Family Monthly Income</div>
    <table class="no-border income">
        <tr><td>₱1,000.00 - ₱4,000.00 ______</td></tr>
        <tr><td>₱5,000.00 - ₱9,000.00 ______</td></tr>
        <tr><td>₱10,000.00 - ₱14,000.00 ______</td></tr>
        <tr><td>₱15,000.00 - ₱19,000.00 ______</td></tr>
        <tr><td>₱20,000.00 - ₱24,000.00 ______</td></tr>
        <tr><td>₱25,000.00 - ₱29,000.00 ______</td></tr>
        <tr><td>₱30,000.00 ______ Others specify ____________</td></tr>
    </table>

    <div class="section-title">Drainage Disposal</div>
    <table class="no-border">
        <tr>
            <td><div class="checkbox"></div> With Blind Drainage</td>
            <td><div class="checkbox"></div> Without Drainage</td>
            <td><div class="checkbox"></div> Open Canal</td>
            <td>Others specify ____________</td>
        </tr>
    </table>

    <div class="section-title">Housing Facilities</div>
    <table class="no-border">
        <tr>
            <td>Barong-barong ______</td>
            <td>Nipa/Bamboo ______</td>
            <td>Wooden House ______</td>
            <td>Semi-Concrete ______</td>
            <td>Concrete ______</td>
        </tr>
        <tr>
            <td colspan="3">No. of Houses: ______</td>
            <td>With Fence ______</td>
            <td>Without Fence ______</td>
        </tr>
    </table>

</body>
</html>
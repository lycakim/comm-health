<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consultation Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .field {
            margin-bottom: 8px;
        }
        .field-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .field-value {
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Consultation Report</h1>
        <p>{{ $consultation->program->name ?? 'No Program' }}</p>
        <p>Generated on: {{ $date }}</p>
    </div>

    <div class="section">
        <div class="section-title">Program Information</div>
        <div class="field">
            <span class="field-label">Program:</span>
            <span class="field-value">{{ $consultation->program->name ?? 'N/A' }}</span>
        </div>
        <div class="field">
            <span class="field-label">Consultation Date:</span>
            <span class="field-value">{{ $consultation->date ? \Carbon\Carbon::parse($consultation->date)->format('F d, Y') : 'N/A' }}</span>
        </div>
        <div class="field">
            <span class="field-label">Consultation ID:</span>
            <span class="field-value">{{ $consultation->id }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Patient Information</div>
        <div class="field">
            <span class="field-label">First Name:</span>
            <span class="field-value">{{ $consultation->patient->first_name ?? 'N/A' }}</span>
        </div>
        <div class="field">
            <span class="field-label">Middle Name:</span>
            <span class="field-value">{{ $consultation->patient->middle_name ?? 'N/A' }}</span>
        </div>
        <div class="field">
            <span class="field-label">Last Name:</span>
            <span class="field-value">{{ $consultation->patient->last_name ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Health Information</div>
        <div class="field">
            <span class="field-label">With Disability:</span>
            <span class="field-value">{{ $consultation->disability ? 'Yes' : 'No' }}</span>
        </div>
        <div class="field">
            <span class="field-label">With Philhealth:</span>
            <span class="field-value">{{ $consultation->philhealth ? 'Yes' : 'No' }}</span>
        </div>
        <div class="field">
            <span class="field-label">4Ps Member:</span>
            <span class="field-value">{{ $consultation->member_of_4ps ? 'Yes' : 'No' }}</span>
        </div>
        <div class="field">
            <span class="field-label">Weight:</span>
            <span class="field-value">{{ $consultation->weight ?? 'Not recorded' }} kg</span>
        </div>
        <div class="field">
            <span class="field-label">Height:</span>
            <span class="field-value">{{ $consultation->height ?? 'Not recorded' }} cm</span>
        </div>
        <div class="field">
            <span class="field-label">Purok:</span>
            <span class="field-value">{{ $consultation->purok->name ?? 'Not specified' }}</span>
        </div>
    </div>

    @if(!empty($consultation->report_field_response))
        <div class="section">
            <div class="section-title">Report Field Responses</div>
            @php
                $responses = $consultation->report_field_response ?? [];
                $reportFields = $consultation->program->report_field ?? [];
                $fieldLabels = collect($reportFields)->pluck('label', 'name')->toArray();
            @endphp
            @foreach($responses as $key => $value)
                <div class="field">
                    <span class="field-label">{{ $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}:</span>
                    <span class="field-value">
                        @if(is_bool($value))
                            {{ $value ? 'Yes' : 'No' }}
                        @elseif(is_array($value))
                            {{ implode(', ', $value) }}
                        @else
                            {{ $value ?? 'Not provided' }}
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="section">
        <div class="section-title">Additional Details</div>
        <div class="field">
            <span class="field-label">Recorded By:</span>
            <span class="field-value">{{ $consultation->user->name ?? 'Not specified' }}</span>
        </div>
        <div class="field">
            <span class="field-label">Created At:</span>
            <span class="field-value">{{ $consultation->created_at ? \Carbon\Carbon::parse($consultation->created_at)->format('F d, Y h:i A') : 'N/A' }}</span>
        </div>
    </div>

    <div class="footer">
        <p>Report generated on {{ $date }}</p>
    </div>
</body>
</html>



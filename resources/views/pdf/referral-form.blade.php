<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Clinical Referral Form</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header img {
            width: 70px;
            vertical-align: middle;
        }
        .section {
            border: 1px solid #000;
            padding: 8px;
            margin-top: 8px;
        }
        .title {
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        td {
            padding: 3px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 25%;
        }
        .line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 100%;
            height: 12px;
        }
        .signature {
            margin-top: 20px;
            text-align: right;
        }
        .ack {
            margin-top: 30px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            {{-- <img src="{{ public_path('comm-health-logo.png') }}" alt="Logo Left"> --}}
            <span style="font-weight:bold; font-size:14px;">Republic of the Philippines<br>
            Province of Davao del Norte<br>
            <span style="color:#004aad;">DCaPS CLUSTER III</span></span><br>
            <small>Email: dcapsspdnc3@gmail.com | Tel No: (084) 628-6592</small>
            {{-- <img src="{{ public_path('comm-health-logo.png') }}" alt="Logo Right"> --}}
        </div>
    </div>

    <h3 class="title">Clinical Referral Form</h3>

    <table>
        <tr>
            <td class="label">Referred to:</td>
            <td>{{ $data['referred_to'] ?? 'CARMEN MHO' }}</td>
            <td class="label">Referred Address:</td>
            <td>{{ $data['referred_address'] ?? 'ISING CARMEN' }}</td>
        </tr>
        <tr>
            <td class="label">Date:</td>
            <td>{{ $data['date'] ?? Carbon\Carbon::now()->format('M d, Y') }}</td>
            <td class="label">Time:</td>
            <td>{{ $data['time'] ?? Carbon\Carbon::now()->format('H:i A') }}</td>
        </tr>
        <tr>
            <td class="label">Name:</td>
            <td colspan="3">{{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Address:</td>
            <td colspan="3">{{ $patient->baragay->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Chief Complaints:</td>
            <td colspan="3">{{ $data['chief_complaints'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Medical History:</td>
            <td colspan="3">{{ $data['medical_history'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">HPI:</td>
            <td colspan="3">{{ $consultation->hpi_notes ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Surgical Operation:</td>
            <td>{{ $consultation->surgical_operation ? 'Yes' : 'No' }}</td>
            <td class="label">If yes, what procedure:</td>
            <td>{{ $consultation->surgical_procedure ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Drug Allergy:</td>
            <td>{{ $consultation->drug_allergy ? 'Yes' : 'No' }}</td>
            <td class="label">If yes, what:</td>
            <td>{{ $consultation->drug_allergy_notes ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Physical Examination:</td>
            <td colspan="3">
                BP: {{ $patient->blood_pressure ?? 'N/A' }} |
                HR: {{ $patient->sugar_level ?? 'N/A' }} |
                WT: {{ $patient->weight ?? 'N/A' }} |
                RR: {{ $patient->respiratory_rate ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td class="label">Impression:</td>
            <td colspan="3">{{ $consultation->impression ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Action Taken:</td>
            <td colspan="3">{{ $consultation->action_taken ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Reason for Referral:</td>
            <td colspan="3">
                {{ $consultation->reason_for_referral ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:15px;">
                <b>Referred by:</b> {{ $referral->user->name ?? ($data['referral_by'] ?? '') }}<br>
                License #: {{ $data['license_no'] ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="section">
        <p><b>To be filled up by recipient hospital upon discharge:</b></p>
        <p><b>Name:</b> {{ $data['recipient_name'] ?? '' }} |
           <b>Age:</b> {{ $data['recipient_age'] ?? '' }} |
           <b>Sex:</b> {{ $data['recipient_sex'] ?? '' }} |
           <b>Date:</b> {{ $data['recipient_date'] ?? '' }}</p>
        <p><b>Diagnosis/Impression:</b> {{ $data['recipient_diagnosis'] ?? '' }}</p>
        <p><b>Medical History:</b> {{ $data['recipient_medical_history'] ?? '' }}</p>
        <p><b>Recommendation/Instructions:</b> {{ $data['recommendation'] ?? '' }}</p>
        <div class="signature">
            <b>{{ $data['recipient_signature'] ?? '' }}</b><br>
            Printed Name and Signature<br>
            {{ $data['recipient_hospital'] ?? '' }}<br>
            {{ $data['recipient_contact'] ?? '' }}
        </div>
    </div>

    <div class="ack">
        <b>ACKNOWLEDGEMENT RECEIPT (to be given to the Driver)</b><br>
        This is to acknowledge that <u>{{ $data['ack_patient'] ?? '' }}</u> was received at
        <u>{{ $data['ack_hospital'] ?? '' }}</u> on <u>{{ $data['ack_date'] ?? '' }}</u>.
    </div>
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>2022 Family/Household Profile Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .header h3 {
            font-size: 11px;
            margin: 2px 0;
        }
        
        .header h2 {
            font-size: 13px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            font-size: 8px;
        }

        .info-section table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .info-section td {
            padding: 1px 4px;
            border: none; /* ensure no border */
            vertical-align: top;
        }

        .info-section strong {
            font-weight: bold;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            padding: 2px 5px;
            border: 1px solid #000;
        }
        
        .info-label {
            font-weight: bold;
            width: 20%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 7px;
            vertical-align: middle;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 7px;
        }
        
        .rotate {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 6px;
        }
        
        .text-left {
            text-align: left;
        }
        
        .sanitation-section {
            border: 1px solid #000;
            padding: 5px;
            margin-top: 10px;
        }
        
        .sanitation-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
            font-size: 9px;
        }
        
        .checkbox-group {
            display: inline-block;
            margin-right: 15px;
            font-size: 7px;
        }
        
        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin-right: 3px;
            vertical-align: middle;
        }
        
        .checkbox.checked::after {
            content: '✓';
            font-size: 8px;
            font-weight: bold;
        }
        
        .footer-notes {
            font-size: 6px;
            margin-top: 5px;
            line-height: 1.3;
        }
        
        .signature-section {
            margin-top: 10px;
            text-align: right;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>Republic of the Philippines</h3>
        <h3>MUNICIPYO/CITY/DISTRICT OF {{ strtoupper($city) ?? '______________' }}</h3>
        <h3>Province/City of {{ strtoupper($province) ?? '______________' }}, Davao Region</h3>
        <h2>2022 FAMILY/ HOUSEHOLD PROFILE FORM</h2>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td style="width: 7%; text-align: left;"><strong>BARANGAY:</strong></td>
                <td style="width: 15%; text-align: left;">{{ strtoupper($barangay->name) }}</td>
                <td style="width: 6%; text-align: left;"><strong>PUROK:</strong></td>
                <td style="width: 15%; text-align: left;">{{  strtoupper($purok->name) ?? '______________' }}</td>
                <td style="width: 6%; text-align: left;"><strong>NHTS:</strong></td>
                <td style="width: 15%; text-align: left;">YES NO</td>
                <td style="width: 6%; text-align: left;"><strong>CCT:</strong></td>
                <td style="width: 15%; text-align: left;">YES NO</td>
            </tr>
            <tr>
                <td style="width: 7%; text-align: left;"><strong>BARANGAY CHAIRMAN:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
                <td style="width: 6%; text-align: left;"><strong>DATE PROFILE:</strong></td>
                <td style="width: 15%; text-align: left;">{{ strtoupper($date) }}</td>
                <td style="width: 6%; text-align: left;"><strong>NHTS / ID NO.:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
                <td style="width: 6%; text-align: left;"><strong>NON-NHTS:</strong></td>
                <td style="width: 15%; text-align: left;">YES NO</td>
            </tr>
            <tr>
                <td style="width: 8%; text-align: left;"><strong>COMMITTEE ON HEALTH:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
                <td style="width: 8%; text-align: left;"><strong>PROFILED / INTERVIEWED BY:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
                <td style="width: 8%; text-align: left;"><strong>IP'S:</strong></td>
                <td style="width: 15%; text-align: left;">YES  NO</td>
                <td style="width: 8%; text-align: left;"><strong>PHILHEALTH ID NO.:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
            </tr>
            <tr>
                <td style="width: 7%; text-align: left;"><strong>MIDWIFE / NDP ASSIGNED:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
                <td style="width: 6%; text-align: left;"><strong></strong></td>
                <td style="width: 15%; text-align: left;"></td>
                <td style="width: 6%; text-align: left;"><strong>TRIBE:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
                <td style="width: 6%; text-align: left;"><strong>IP ID NO.:</strong></td>
                <td style="width: 15%; text-align: left;">_________________</td>
            </tr>
        </table>
    </div>

    @foreach($households as $household)
    <table>
        <thead>
            <tr>
                <th rowspan="3" style="width: 3%;">FAMILY<br>SERIAL<br>NO.</th>
                <th colspan="3" style="width: 25%;">NAME OF HOUSEHOLD MEMBERS</th>
                <th rowspan="3" style="width: 8%;">Relation<br>ship to the<br>Head of<br>the Family</th>
                <th rowspan="3" style="width: 6%;">Date of<br>Birth &<br>Age</th>
                <th rowspan="3" style="width: 5%;">Place of<br>Birth</th>
                <th rowspan="3" style="width: 3%;">Sex</th>
                <th rowspan="3" style="width: 4%;">Civil<br>Status</th>
                <th rowspan="3" style="width: 6%;">Educ.<br>Attain-<br>ment<br>occupation</th>
                <th rowspan="3" style="width: 6%;">PhilHealth<br>ID Number<br>& Date of<br>Expiration</th>
                <th colspan="2" style="width: 6%;">Health Status</th>
                <th colspan="2" style="width: 8%;">WRA (15-49 Y.O.)</th>
                <th colspan="4" style="width: 12%;">Child Health Status</th>
                <th rowspan="3" style="width: 5%;">Trained<br>on BaSiCG<br>Support/<br>First Aid</th>
            </tr>
            <tr>
                <th rowspan="2" style="width: 10%;">LAST NAME</th>
                <th rowspan="2" style="width: 10%;">FIRST NAME</th>
                <th rowspan="2" style="width: 5%;">MIDDLE NAME</th>
                <th rowspan="2"><span class="rotate">HPN/HLTHCR</span></th>
                <th rowspan="2"><span class="rotate">ILL PWD SLM FMPLNG</span></th>
                <th rowspan="2"><span class="rotate">Current User (FP memo)</span></th>
                <th rowspan="2"><span class="rotate">Pregnant</span></th>
                <th rowspan="2"><span class="rotate">Nut. Status</span></th>
                <th rowspan="2"><span class="rotate">Height</span></th>
                <th rowspan="2"><span class="rotate">Weight</span></th>
                <th rowspan="2"><span class="rotate">FIC(YN)</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($household->members as $member)
            <tr>
                <td>{{ $loop->parent->iteration }}</td>
                <td class="text-left">{{ $member->last_name }}</td>
                <td class="text-left">{{ $member->first_name }}</td>
                <td class="text-left">{{ $member->middle_name }}</td>
                <td>{{ $member->relationship }}</td>
                <td>{{ $member->birth_date?->format('m-d-Y') }}<br>{{ $member->age }}</td>
                <td>{{ $member->place_of_birth }}</td>
                <td>{{ $member->sex }}</td>
                <td>{{ $member->civil_status }}</td>
                <td class="text-left">{{ $member->education }}<br>{{ $member->occupation }}</td>
                <td>{{ $member->philhealth_number }}<br>{{ $member->philhealth_expiry?->format('m/d/Y') }}</td>
                <td>{{ $member->health_status }}</td>
                <td>{{ $member->illness_pwd_4ps }}</td>
                <td>{{ $member->is_fp_user ? 'Y' : '' }}</td>
                <td>{{ $member->is_pregnant ? 'Y' : '' }}</td>
                <td>{{ $member->nutritional_status }}</td>
                <td>{{ $member->height }}</td>
                <td>{{ $member->weight }}</td>
                <td>{{ $member->is_fic ? 'Y' : '' }}</td>
                <td>{{ $member->has_basic_support_training ? 'Y' : '' }}</td>
            </tr>
            @endforeach
            
            {{-- Add empty rows if less than 10 members --}}
            @for($i = count($household->members); $i < 10; $i++)
            <tr>
                <td>{{ $loop->parent->iteration }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    <div class="sanitation-section">
        <div class="sanitation-title">SANITATION STATUS</div>
        <div style="display: flex; justify-content: space-between;">
            <div style="flex: 1;">
                <strong style="font-size: 8px;">Sources of Water Supply:</strong><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'community' ? 'checked' : '' }}"></span> 1) community water system
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'spring' ? 'checked' : '' }}"></span> 2) developed spring
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'artesian' ? 'checked' : '' }}"></span> 3) protected well
                </label>
                
                <br><br>
                <strong style="font-size: 8px;">Type of Toilet Facility:</strong><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->toilet_type == 'water_sealed' ? 'checked' : '' }}"></span> 1) water sealed/flush toilet
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->toilet_type == 'closed_pit' ? 'checked' : '' }}"></span> 2) closed pit or privy
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->toilet_type == 'open_pit' ? 'checked' : '' }}"></span> 3) open pit or privy
                </label>
            </div>
            
            <div style="flex: 1;">
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'truck' ? 'checked' : '' }}"></span> 4) truck/tanker peddler
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'bottled' ? 'checked' : '' }}"></span> 5) bottled water
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'undeveloped_spring' ? 'checked' : '' }}"></span> 6) undeveloped spring
                </label>
                
                <br><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->toilet_type == 'communal' ? 'checked' : '' }}"></span> 4) communal toilet
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->toilet_type == 'drop_overhang' ? 'checked' : '' }}"></span> 5) drop/overhang
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->toilet_type == 'field_body_water' ? 'checked' : '' }}"></span> 6) field/body of water
                </label>
            </div>
            
            <div style="flex: 1;">
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'unprotected' ? 'checked' : '' }}"></span> 7) unprotected well
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'rainwater' ? 'checked' : '' }}"></span> 8) rainwater
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'river_stream_dam' ? 'checked' : '' }}"></span> 9) river, stream or dam
                </label>
                
                <br><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->water_source == 'other' ? 'checked' : '' }}"></span> 7) other, specify: ____________
                </label><br>
                <label class="checkbox-group">
                    <span class="checkbox {{ $household->toilet_type == 'none' ? 'checked' : '' }}"></span> 8) no toilet
                </label>
            </div>
        </div>
        
        <div class="signature-section">
            <p>Signature over printed name of BHW / Interviewer</p>
            <br>
            <p>___________________________________</p>
            <br>
            <p>Signature over printed name of Encoder & designation</p>
            <br>
            <p>Date Encoded: ______________</p>
        </div>
    </div>

    <div class="footer-notes">
        <strong>Relation to Head of Family:</strong> spouse; child; live-in partner; to wife; son-in-law; daughter-in-law; parent; sibling; grandparent; grandchild<br>
        <strong>Sex:</strong> M - Male; F - Female &nbsp;&nbsp;&nbsp; <strong>Civil Status:</strong> S - Married; S - Single; W - Widow/Widower; SP - Separated; LI - Live-in<br>
        <strong>Educational Attainment:</strong> E - None; EL - elementary level; EG - elementary graduate; HS - High School level; HG - high school graduate<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; V - vocational; CL - college level; CG - college graduate; PG - post graduate<br>
        <strong>Occupation:</strong> GE - gov't employee; PR - private employee; FA - Farmer; FS - Fisherman; DRV - housekeeping/housewife; LAB - laborer/construction worker; V - vendor; Driver<br>
        <strong>Health Status:</strong> HPN - Hypertension; DM - DIABETES MELLITUS; TB - tuberculosis; CA - cancer; UI - mental illness; FWD - persons with disability; SM - Smokers; F/MDD - persons who use drug; IBT - basal body temp; RTI - symptomal control method; LAN - lactating or breastfeeding<br>
        <strong>ILL-PWD-SM:</strong> N - normal; UW - underweight; St - stunted; W - wasting; OW - overweight
    </div>

    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
    @endforeach
</body>
</html>
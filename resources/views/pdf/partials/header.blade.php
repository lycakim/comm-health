<div class="header-banner" style="background: #2563eb; padding: 15px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
        <div style="flex-shrink: 0;">
            <img src="{{ public_path('comm-health-icon.png') }}" alt="Municipality Logo" style="height: 70px; width: auto; display: block; background: white; padding: 5px; border-radius: 50%;">
        </div>
        <div style="text-align: right; color: white; flex-grow: 1;">
            <p style="margin: 0; font-size: 11px; font-weight: normal;">Republic of the Philippines</p>
            <p style="margin: 2px 0 0 0; font-size: 11px; font-weight: normal;">Province of {{ strtoupper($province ?? 'DAVAO DEL NORTE') }}</p>
            <p style="margin: 2px 0 0 0; font-size: 12px; font-weight: bold;">MUNICIPALITY OF {{ strtoupper($municipality ?? 'CARMEN') }}</p>
            <p style="margin: 2px 0 0 0; font-size: 12px; font-weight: bold;">MUNICIPAL HEALTH OFFICE</p>
        </div>
    </div>
</div>
<div class="header-details" style="text-align: center; margin-bottom: 15px;">
    <p style="font-size: 14px; font-weight: bold; margin: 0 0 5px 0;">{{ strtoupper($reportTitle ?? 'Report') }}</p>
    <p style="font-size: 11px; font-weight: bold; margin: 0 0 10px 0; text-decoration: underline;">{{ isset($barangayName) ? 'BARANGAY: ' . strtoupper($barangayName) : '' }}</p>
    @if(isset($purok))
    <p style="font-size: 10px; margin: 0 0 3px 0;">PUROK: {{ $purok ?? '_______________' }}</p>
    @endif
    @if(isset($category))
    <p style="font-size: 10px; margin: 0 0 3px 0;">CATEGORY: {{ $category ?? '_______________' }}</p>
    @endif
    <p style="font-size: 10px; margin: 10px 0 0 0;">As of: {{ $dateTime ?? now()->format('F d, Y h:i A') }}</p>
</div>

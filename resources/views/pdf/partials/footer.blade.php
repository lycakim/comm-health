<div class="report-footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc;">
    <p style="font-size: 14px; margin: 0 0 15px 0;">TOTAL: <span style="text-decoration: underline;">{{ $totalRecords ?? 0 }}</span></p>
    <div style="display: flex; justify-content: space-between; margin-top: 30px;">
        <div style="width: 45%;">
            <p style="font-size: 13px; margin: 0 0 5px 0;">PREPARED BY:</p>
            <p style="font-size: 14px; margin: 0 0 25px 0; border-bottom: 1px solid #000; padding-bottom: 2px;">{{ $preparedByName ?? '(name sa user)' }}</p>
            <p style="font-size: 13px; margin: 0;">{{ $preparedByTitle ?? 'BHW' }} - {{ $barangayName ?? '' }}</p>
        </div>
        <div style="width: 45%; text-align: right;">
            <p style="font-size: 13px; margin: 0 0 5px 0;">NOTED BY:</p>
            <p style="font-size: 14px; margin: 0 0 25px 0; border-bottom: 1px solid #000; padding-bottom: 2px;">_________________________</p>
            <p style="font-size: 13px; margin: 0;">Municipal Health Office - Admin</p>
        </div>
    </div>
</div>

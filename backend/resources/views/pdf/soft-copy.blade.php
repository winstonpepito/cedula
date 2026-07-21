<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Community Tax Certificate {{ $application->tracking_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #102a2d; font-size: 12px; }
        .frame { border: 2px solid #0d7377; padding: 28px; }
        .title { text-align: center; font-size: 20px; font-weight: bold; color: #0d7377; letter-spacing: 1px; }
        .subtitle { text-align: center; margin: 6px 0 22px; color: #5a6b6e; }
        .grid td { padding: 7px 4px; vertical-align: top; }
        .label { width: 170px; color: #5a6b6e; }
        .amount-box { margin-top: 20px; background: #e8f4f4; padding: 14px; }
        .footer { margin-top: 24px; font-size: 10px; color: #7a8a8c; text-align: center; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="title">COMMUNITY TAX CERTIFICATE</div>
        <div class="subtitle">eCedula Soft Copy · {{ $application->tracking_number }}</div>

        <table class="grid" width="100%">
            <tr>
                <td class="label">Taxpayer</td>
                <td><strong>{{ $application->applicantName() }}</strong></td>
            </tr>
            <tr>
                <td class="label">Type</td>
                <td>{{ ucfirst($application->applicant_type) }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td>{{ $application->address_line }}, {{ $application->barangay?->name }}{{ $application->city ? ', '.$application->city : '' }}</td>
            </tr>
            <tr>
                <td class="label">TIN</td>
                <td>{{ $application->tin ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Occupation / Business</td>
                <td>{{ $application->occupation ?: ($application->corporation_name ?: '—') }}</td>
            </tr>
            <tr>
                <td class="label">Year</td>
                <td>{{ optional($application->paid_at)->format('Y') ?? now()->format('Y') }}</td>
            </tr>
        </table>

        <div class="amount-box">
            <div>Basic Tax: PHP {{ number_format($application->base_tax, 2) }}</div>
            <div>Additional Tax: PHP {{ number_format($application->additional_tax, 2) }}</div>
            <div>Interest: PHP {{ number_format($application->interest_amount, 2) }}</div>
            <div style="margin-top:8px;font-size:15px;font-weight:bold;">
                Community Tax Paid: PHP {{ number_format($application->community_tax_total + $application->interest_amount, 2) }}
            </div>
        </div>

        <div style="text-align:center;margin-top:22px;">
            @if (!empty($qrDataUri))
                <img src="{{ $qrDataUri }}" width="120" height="120" alt="QR">
            @endif
            <div style="font-size:10px;color:#5a6b6e;margin-top:6px;">{{ $trackUrl }}</div>
        </div>

        <div class="footer">
            This is a system-generated soft copy for eCedula. Present the tracking number for verification.
        </div>
    </div>
</body>
</html>

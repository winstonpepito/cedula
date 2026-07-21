<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>eCedula Application {{ $application->tracking_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f2a2e; font-size: 12px; }
        .header { border-bottom: 3px solid #0d7377; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { font-size: 26px; font-weight: bold; color: #0d7377; }
        .sub { color: #5a6b6e; margin-top: 4px; }
        h2 { font-size: 14px; color: #0d7377; margin: 18px 0 8px; border-bottom: 1px solid #d7e2e3; padding-bottom: 4px; }
        .row { margin-bottom: 5px; }
        .label { color: #5a6b6e; width: 170px; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 7px 10px; border-bottom: 1px solid #d7e2e3; text-align: left; }
        th { background: #e8f4f4; }
        .total { font-size: 15px; font-weight: bold; }
        .qr { text-align: center; margin-top: 20px; }
        .footer { margin-top: 24px; font-size: 10px; color: #7a8a8c; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">eCedula</div>
        <div class="sub">Application summary — receipt, applicant details, and address</div>
    </div>

    <div class="row"><span class="label">Tracking No.</span> <strong>{{ $application->tracking_number }}</strong></div>
    <div class="row"><span class="label">Status</span> {{ str_replace('_', ' ', $application->status) }}</div>
    <div class="row"><span class="label">Delivery mode</span> {{ str_replace('_', ' ', $application->delivery_mode) }}</div>
    <div class="row"><span class="label">Paid at</span> {{ optional($application->paid_at)->format('M d, Y h:i A') ?? '—' }}</div>
    <div class="row"><span class="label">Submitted</span> {{ optional($application->created_at)->format('M d, Y h:i A') }}</div>

    <h2>Applicant details</h2>
    <div class="row"><span class="label">Type</span> {{ ucfirst($application->applicant_type) }}</div>
    <div class="row"><span class="label">Full name / Corp.</span> {{ $application->applicantName() }}</div>
    @if ($application->applicant_type === 'individual')
        <div class="row"><span class="label">First name</span> {{ $application->first_name }}</div>
        <div class="row"><span class="label">Middle name</span> {{ $application->middle_name ?: '—' }}</div>
        <div class="row"><span class="label">Last name</span> {{ $application->last_name }}</div>
        <div class="row"><span class="label">Birthdate</span> {{ optional($application->birthdate)->format('M d, Y') ?? '—' }}</div>
        <div class="row"><span class="label">Civil status</span> {{ $application->civil_status ?: '—' }}</div>
        <div class="row"><span class="label">Citizenship</span> {{ $application->citizenship ?: '—' }}</div>
        <div class="row"><span class="label">Occupation</span> {{ $application->occupation ?: '—' }}</div>
    @else
        <div class="row"><span class="label">Corporation name</span> {{ $application->corporation_name }}</div>
    @endif
    <div class="row"><span class="label">Email</span> {{ $application->email }}</div>
    <div class="row"><span class="label">Phone</span> {{ $application->phone ?: '—' }}</div>
    <div class="row"><span class="label">TIN</span> {{ $application->tin ?: '—' }}</div>

    @if ($application->applicant_type === 'individual')
        <h2>Income details</h2>
        <div class="row"><span class="label">Monthly salary</span> PHP {{ number_format((float) ($application->monthly_salary ?? 0), 2) }}</div>
        <div class="row"><span class="label">13th month</span> PHP {{ number_format((float) ($application->thirteenth_month ?? 0), 2) }}</div>
        <div class="row"><span class="label">Other bonuses</span> PHP {{ number_format((float) ($application->other_bonuses ?? 0), 2) }}</div>
        <div class="row"><span class="label">Annual income</span> PHP {{ number_format((float) ($application->annual_income ?? 0), 2) }}</div>
    @else
        <h2>Business details</h2>
        <div class="row"><span class="label">Property value</span> PHP {{ number_format((float) ($application->property_value ?? 0), 2) }}</div>
        <div class="row"><span class="label">Gross receipts</span> PHP {{ number_format((float) ($application->gross_receipts ?? 0), 2) }}</div>
    @endif

    <h2>Address</h2>
    <div class="row"><span class="label">Street / line</span> {{ $application->address_line }}</div>
    <div class="row"><span class="label">Barangay</span> {{ $application->barangay?->name ?: '—' }}</div>
    <div class="row"><span class="label">City / Municipality</span> {{ $application->city ?: '—' }}</div>
    <div class="row"><span class="label">Province</span> {{ $application->province ?: '—' }}</div>

    <h2>Receipt / computation</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:right">Amount (PHP)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Community Tax</td>
                <td style="text-align:right">{{ number_format((float) $application->base_tax, 2) }}</td>
            </tr>
            <tr>
                <td>Additional Community Tax</td>
                <td style="text-align:right">{{ number_format((float) $application->additional_tax, 2) }}</td>
            </tr>
            <tr>
                <td>Late Interest ({{ $application->interest_months }} mo.)</td>
                <td style="text-align:right">{{ number_format((float) $application->interest_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Delivery Fee</td>
                <td style="text-align:right">{{ number_format((float) $application->delivery_fee, 2) }}</td>
            </tr>
            <tr>
                <td>Convenience Fee</td>
                <td style="text-align:right">{{ number_format((float) $application->convenience_fee, 2) }}</td>
            </tr>
            <tr>
                <td>Server Fee</td>
                <td style="text-align:right">{{ number_format((float) $application->server_fee, 2) }}</td>
            </tr>
            <tr>
                <td>Payment Processor Fee</td>
                <td style="text-align:right">{{ number_format((float) $application->payment_processor_fee, 2) }}</td>
            </tr>
            <tr>
                <td class="total">Total Due</td>
                <td class="total" style="text-align:right">{{ number_format((float) $application->total_due, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="qr">
        @if (!empty($qrDataUri))
            <img src="{{ $qrDataUri }}" width="120" height="120" alt="QR">
            <div style="margin-top:8px;">Scan to view transaction online</div>
        @endif
        <div style="font-size:10px;color:#5a6b6e;">{{ $trackUrl }}</div>
    </div>

    <div class="footer">
        Generated by eCedula for staff use. Tracking {{ $application->tracking_number }}.
    </div>
</body>
</html>

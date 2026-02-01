<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header-bg {
            background-color: #f8fafc;
            border-bottom: 4px solid #3b82f6; /* Blue accent matching Filament default */
            padding: 40px 40px 20px 40px;
        }
        .container {
            padding: 40px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo {
            max-height: 60px;
            max-width: 200px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
            margin: 0;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #3b82f6;
            text-align: right;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .invoice-meta {
            text-align: right;
            margin-top: 10px;
            color: #64748b;
        }
        .meta-label {
            font-weight: bold;
            color: #475569;
            display: inline-block;
            width: 80px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        .badge-dp { background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .badge-pelunasan { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-termin { background-color: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .badge-other { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .info-grid {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 40px;
            border-collapse: collapse;
        }
        .info-col {
            width: 50%;
            vertical-align: top;
        }
        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .info-content {
            font-size: 14px;
            line-height: 1.5;
            color: #1e293b;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .items-table .description {
            font-weight: 500;
        }

        .totals-container {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-spacer {
            width: 60%;
        }
        .totals-table {
            width: 40%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 0;
            text-align: right;
        }
        .totals-label {
            color: #64748b;
            padding-right: 15px !important;
        }
        .totals-value {
            color: #1e293b;
            font-weight: 500;
        }
        .grand-total-row td {
            border-top: 2px solid #3b82f6;
            padding-top: 15px;
            padding-bottom: 15px;
        }
        .grand-total-label {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
        }
        .grand-total-value {
            font-size: 20px;
            font-weight: bold;
            color: #3b82f6;
        }

        .footer-section {
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 30px;
        }
        .footer-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-col {
            vertical-align: top;
            padding-right: 30px;
        }
        .notes-section {
            width: 60%;
        }
        .payment-section {
            width: 40%;
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 10px;
            display: block;
        }
        .text-sm {
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        
        .thank-you {
            text-align: center;
            margin-top: 60px;
            font-size: 14px;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header-bg">
        <table class="header-table">
            <tr>
                <td style="vertical-align: top;">
                    @if($invoice->company_logo)
                        <img src="{{ public_path('storage/' . $invoice->company_logo) }}" class="logo" alt="Logo">
                    @else
                        <h1 class="company-name">{{ $invoice->company_name ?? 'COMPANY NAME' }}</h1>
                    @endif
                    <div style="margin-top: 10px; color: #64748b; font-size: 12px;">
                        {!! nl2br(e($invoice->company_address)) !!}<br>
                        {{ $invoice->company_phone }}
                    </div>
                </td>
                <td style="vertical-align: top; text-align: right;">
                    <h1 class="invoice-title">INVOICE</h1>
                    <div class="invoice-meta">
                        <span class="meta-label">NO:</span> {{ $invoice->invoice_number }}<br>
                        <span class="meta-label">DATE:</span> {{ $invoice->issue_date->format('d M Y') }}<br>
                        <span class="meta-label">DUE:</span> {{ $invoice->due_date->format('d M Y') }}
                    </div>
                    <div class="badge badge-{{ $invoice->type }}">
                        {{ strtoupper($invoice->type) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="container">
        <table class="info-grid">
            <tr>
                <td class="info-col">
                    <div class="info-label">Bill To</div>
                    <div class="info-content">
                        <strong>{{ $invoice->client_name }}</strong><br>
                        {!! nl2br(e($invoice->client_address)) !!}
                    </div>
                </td>
                <td class="info-col" style="text-align: right;">
                    <!-- Optional: Project Info or Empty for balance -->
                    <div class="info-label">Project</div>
                    <div class="info-content">
                        {{ $invoice->project->name ?? '-' }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="50%">Description</th>
                    <th width="10%" class="text-center">Qty</th>
                    <th width="20%" class="text-right">Price</th>
                    <th width="20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @if(is_array($invoice->items))
                    @foreach($invoice->items as $item)
                        <tr>
                            <td class="description">{{ $item['description'] ?? '' }}</td>
                            <td class="text-center">{{ $item['quantity'] ?? 0 }}</td>
                            <td class="text-right">Rp {{ number_format($item['unit_price'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item['total'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <table class="totals-container">
            <tr>
                <td class="totals-spacer"></td>
                <td>
                    <table class="totals-table" style="width: 100%;">
                        <tr>
                            <td class="totals-label">Subtotal</td>
                            <td class="totals-value">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($invoice->tax_rate > 0)
                        <tr>
                            <td class="totals-label">Tax ({{ $invoice->tax_rate }}%)</td>
                            <td class="totals-value">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="grand-total-row">
                            <td class="grand-total-label">TOTAL</td>
                            <td class="grand-total-value">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer-section">
            <table class="footer-grid">
                <tr>
                    <td class="footer-col notes-section">
                        <span class="section-title">Notes & Terms</span>
                        <div class="text-sm">
                            {!! nl2br(e($invoice->notes)) !!}
                        </div>
                    </td>
                    <td class="footer-col">
                        <div class="payment-section">
                            <span class="section-title">Payment Details</span>
                            <div class="text-sm" style="font-family: monospace;">
                                {!! nl2br(e($invoice->company_bank_account)) !!}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="thank-you">
            Thank you for your business!
        </div>
    </div>
</body>
</html>

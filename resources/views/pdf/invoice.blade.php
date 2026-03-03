<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->document_identifier }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .container { padding: 20px 30px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .header-left { width: 60%; }
        .header-right { width: 35%; text-align: right; }
        .company-name { font-size: 18px; font-weight: bold; color: #4f46e5; margin-bottom: 5px; }
        .invoice-title { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .invoice-number { font-size: 14px; color: #666; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-validated { background: #dbeafe; color: #1e40af; }
        .status-signed { background: #e0e7ff; color: #3730a3; }
        .status-draft { background: #f3f4f6; color: #4b5563; }
        .status-submitted { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 48%; }
        .info-box-title { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-bottom: 5px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .info-box p { margin-bottom: 2px; }
        .info-box .name { font-weight: bold; font-size: 12px; }
        .dates-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .date-item { padding: 6px 12px; background: #f9fafb; border-radius: 4px; }
        .date-item .label { font-size: 8px; text-transform: uppercase; color: #888; }
        .date-item .value { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        thead th { background: #4f46e5; color: white; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        thead th.right { text-align: right; }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tbody td.right { text-align: right; font-family: monospace; }
        tbody td.code { font-family: monospace; font-size: 9px; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .totals-section { display: flex; justify-content: flex-end; margin-top: 15px; }
        .totals-table { width: 280px; }
        .totals-table tr td { padding: 4px 8px; border: none; }
        .totals-table tr td:last-child { text-align: right; font-family: monospace; }
        .totals-table .grand-total { font-size: 14px; font-weight: bold; border-top: 2px solid #4f46e5; color: #4f46e5; }
        .amount-words { margin-top: 10px; padding: 8px 12px; background: #eef2ff; border-left: 3px solid #4f46e5; font-style: italic; font-size: 10px; }
        .tax-summary { margin-top: 20px; }
        .tax-summary h3 { font-size: 11px; margin-bottom: 5px; color: #4f46e5; }
        .tax-summary table thead th { background: #6b7280; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 10px; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-left { font-size: 8px; color: #888; }
        .qr-code { text-align: right; }
        .qr-code img { width: 80px; height: 80px; }
        .bank-info { margin-top: 15px; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 4px; }
        .bank-info h4 { font-size: 9px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        .notes { margin-top: 15px; padding: 8px 12px; background: #fefce8; border-radius: 4px; }
        .notes h4 { font-size: 9px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        .teif-badge { display: inline-block; padding: 2px 6px; background: #f0fdf4; border: 1px solid #22c55e; border-radius: 3px; font-size: 8px; color: #166534; margin-left: 8px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="container">
        {{-- HEADER --}}
        <table style="width:100%; margin-bottom: 25px; border: none;">
            <tr>
                <td style="width: 60%; border: none; padding: 0; vertical-align: top;">
                    @if($company && $company->logo_path)
                        <img src="{{ storage_path('app/public/' . $company->logo_path) }}" alt="Logo" style="max-height: 50px; margin-bottom: 8px;">
                    @endif
                    <div class="company-name">{{ $company->company_name ?? 'Company Name' }}</div>
                    @if($sender)
                        <p>{{ $sender->street }}</p>
                        <p>{{ $sender->postal_code }} {{ $sender->city }}</p>
                        <p>ID: {{ $sender->partner_identifier }}</p>
                    @endif
                    @if($company && $company->phone)<p>Tel: {{ $company->phone }}</p>@endif
                    @if($company && $company->email)<p>{{ $company->email }}</p>@endif
                </td>
                <td style="width: 40%; border: none; padding: 0; vertical-align: top; text-align: right;">
                    <div class="invoice-title">INVOICE <span class="teif-badge">TEIF</span></div>
                    <div class="invoice-number">N° {{ $invoice->document_identifier }}</div>
                    <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                    @if($invoice->ref_ttn_value)
                        <p style="margin-top: 8px; font-size: 9px; color: #888;">TTN Ref: {{ $invoice->ref_ttn_value }}</p>
                    @endif
                </td>
            </tr>
        </table>

        {{-- DATES --}}
        <table style="width:100%; margin-bottom: 20px; border: none;">
            <tr>
                <td style="border: none; padding: 4px 10px; background: #f9fafb; border-radius: 4px;">
                    <span style="font-size: 8px; text-transform: uppercase; color: #888;">Invoice Date</span><br>
                    <strong>{{ $invoice->invoice_date ?? 'N/A' }}</strong>
                </td>
                <td style="border: none; padding: 4px 10px; background: #f9fafb; border-radius: 4px;">
                    <span style="font-size: 8px; text-transform: uppercase; color: #888;">Document Type</span><br>
                    <strong>{{ $invoice->document_type_code }}</strong> - {{ $invoice->document_type_name }}
                </td>
                <td style="border: none; padding: 4px 10px; background: #f9fafb; border-radius: 4px;">
                    <span style="font-size: 8px; text-transform: uppercase; color: #888;">Version</span><br>
                    <strong>{{ $invoice->version ?? '1.8.8' }}</strong>
                </td>
            </tr>
        </table>

        {{-- SENDER & RECEIVER --}}
        <table style="width:100%; margin-bottom: 20px; border: none;">
            <tr>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                    <div class="info-box-title">SENDER (I-62)</div>
                    @if($sender)
                        <p class="name">{{ $sender->partner_name }}</p>
                        @if($sender->street)
                            <p>{{ $sender->street }}@if($sender->city), {{ $sender->city }}@endif</p>
                        @endif
                        @if($sender->postal_code || $sender->country)
                            <p>{{ $sender->postal_code }} {{ $sender->country }}</p>
                        @endif
                        <p>{{ $sender->partner_identifier_type }}: {{ $sender->partner_identifier }}</p>
                    @else
                        <p>ID: {{ $invoice->sender_identifier }}</p>
                    @endif
                </td>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                    <div class="info-box-title">RECEIVER (I-64)</div>
                    @if($receiver)
                        <p class="name">{{ $receiver->partner_name }}</p>
                        @if($receiver->street)
                            <p>{{ $receiver->street }}@if($receiver->city), {{ $receiver->city }}@endif</p>
                        @endif
                        @if($receiver->postal_code || $receiver->country)
                            <p>{{ $receiver->postal_code }} {{ $receiver->country }}</p>
                        @endif
                        <p>{{ $receiver->partner_identifier_type }}: {{ $receiver->partner_identifier }}</p>
                    @else
                        <p>ID: {{ $invoice->receiver_identifier }}</p>
                    @endif
                </td>
            </tr>
        </table>

        {{-- LINES TABLE --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 12%;">Code</th>
                    <th style="width: 35%;">Description</th>
                    <th class="right" style="width: 10%;">Qty</th>
                    <th style="width: 8%;">Unit</th>
                    <th class="right" style="width: 12%;">Unit Price</th>
                    <th class="right" style="width: 8%;">VAT%</th>
                    <th class="right" style="width: 12%;">Net Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lines as $index => $line)
                @php
                    $unitPrice = '0.000';
                    $netAmount = '0.000';
                    if (is_array($line->amounts)) {
                        foreach ($line->amounts as $amount) {
                            if (($amount['amount_type_code'] ?? '') === 'I-183') {
                                $unitPrice = $amount['amount'] ?? '0.000';
                            }
                            if (($amount['amount_type_code'] ?? '') === 'I-171') {
                                $netAmount = $amount['amount'] ?? '0.000';
                            }
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $line->item_identifier ?? ($index + 1) }}</td>
                    <td class="code">{{ $line->item_code }}</td>
                    <td>{{ $line->item_description }}</td>
                    <td class="right">{{ number_format((float)$line->quantity, 3, '.', '') }}</td>
                    <td>{{ $line->measurement_unit }}</td>
                    <td class="right">{{ number_format((float)$unitPrice, 3, '.', '') }}</td>
                    <td class="right">{{ number_format((float)$line->tax_rate, 0) }}%</td>
                    <td class="right"><strong>{{ number_format((float)$netAmount, 3, '.', '') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TOTALS --}}
        <table style="width: 280px; margin-left: auto; border: none;">
            <tr><td style="border:none; padding:4px 8px; color:#666;">Total HT (I-176)</td><td style="border:none; padding:4px 8px; text-align:right; font-family:monospace;">{{ number_format((float)$invoice->total_ht, 3, '.', '') }} TND</td></tr>
            <tr><td style="border:none; padding:4px 8px; color:#666;">Total TVA (I-181)</td><td style="border:none; padding:4px 8px; text-align:right; font-family:monospace;">{{ number_format((float)$invoice->total_tva, 3, '.', '') }} TND</td></tr>
            <tr class="grand-total">
                <td style="border:none; padding:6px 8px; font-size:14px; font-weight:bold; border-top:2px solid #4f46e5; color:#4f46e5;">Total TTC (I-180)</td>
                <td style="border:none; padding:6px 8px; text-align:right; font-family:monospace; font-size:14px; font-weight:bold; border-top:2px solid #4f46e5; color:#4f46e5;">{{ number_format((float)$invoice->total_ttc, 3, '.', '') }} TND</td>
            </tr>
        </table>

        {{-- AMOUNT IN WORDS --}}
        @if(!empty($amountInWords))
        <div class="amount-words">
            Amount in words: <strong>{{ $amountInWords }}</strong>
        </div>
        @endif

        {{-- TAX SUMMARY --}}
        @if($invoice->taxes && $invoice->taxes->count() > 0)
        <div class="tax-summary">
            <h3>Tax Summary</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tax Type</th>
                        <th class="right">Rate</th>
                        <th class="right">Taxable Amount</th>
                        <th class="right">Tax Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->taxes as $tax)
                    @php
                        $taxableAmount = '0.000';
                        $taxAmount = '0.000';
                        if (is_array($tax->amounts)) {
                            foreach ($tax->amounts as $amount) {
                                if (($amount['amount_type_code'] ?? '') === 'I-177') {
                                    $taxableAmount = $amount['amount'] ?? '0.000';
                                }
                                if (($amount['amount_type_code'] ?? '') === 'I-178') {
                                    $taxAmount = $amount['amount'] ?? '0.000';
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $tax->tax_type_code }} - {{ $tax->tax_type_name }}</td>
                        <td class="right">{{ number_format((float)$tax->tax_rate, 2) }}%</td>
                        <td class="right">{{ number_format((float)$taxableAmount, 3, '.', '') }} TND</td>
                        <td class="right">{{ number_format((float)$taxAmount, 3, '.', '') }} TND</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- BANK INFO --}}
        @if($company && ($company->bank_name || $company->bank_rib))
        <div class="bank-info">
            <h4>Bank Details</h4>
            @if($company->bank_name)<p>Bank: {{ $company->bank_name }}</p>@endif
            @if($company->bank_rib)<p>RIB: {{ $company->bank_rib }}</p>@endif
            @if($company->postal_account)<p>CCP: {{ $company->postal_account }}</p>@endif
        </div>
        @endif

        {{-- NOTES --}}
        @if($invoice->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        {{-- FOOTER --}}
        <table style="width:100%; margin-top:30px; border:none; border-top:1px solid #e5e7eb; padding-top:10px;">
            <tr>
                <td style="border:none; padding:10px 0 0 0; vertical-align:bottom;">
                    @if($company)
                        <p style="font-size:8px; color:#888;">{{ $company->company_name }} — MF: {{ $company->matricule_fiscale }}</p>
                        <p style="font-size:8px; color:#888;">{{ $company->address_street }}, {{ $company->address_postal_code }} {{ $company->address_city }}</p>
                    @endif
                    <p style="font-size:7px; color:#aaa; margin-top:4px;">TEIF Document — Generated on {{ now()->format('d/m/Y H:i') }} — NovERP</p>
                    <p style="font-size:7px; color:#aaa;">Standard: TEIF XML v{{ $invoice->version ?? '1.8.8' }} — Tunisia Tax Network</p>
                </td>
                <td style="border:none; padding:10px 0 0 0; text-align:right; vertical-align:bottom; width:100px;">
                    @if(!empty($qrCode))
                        <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" style="width:80px; height:80px;">
                        <p style="font-size:7px; color:#888; margin-top:2px;">CEV QR Code</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

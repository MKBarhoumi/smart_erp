<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $oldinvoice->oldinvoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .container { padding: 20px 30px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .header-left { width: 60%; }
        .header-right { width: 35%; text-align: right; }
        .company-name { font-size: 18px; font-weight: bold; color: #1a56db; margin-bottom: 5px; }
        .oldinvoice-title { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .oldinvoice-number { font-size: 14px; color: #666; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-validated { background: #dbeafe; color: #1e40af; }
        .status-signed { background: #e0e7ff; color: #3730a3; }
        .status-draft { background: #f3f4f6; color: #4b5563; }
        .status-submitted { background: #fef3c7; color: #92400e; }
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
        thead th { background: #1e40af; color: white; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        thead th.right { text-align: right; }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tbody td.right { text-align: right; font-family: monospace; }
        tbody td.code { font-family: monospace; font-size: 9px; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .totals-section { display: flex; justify-content: flex-end; margin-top: 15px; }
        .totals-table { width: 280px; }
        .totals-table tr td { padding: 4px 8px; border: none; }
        .totals-table tr td:last-child { text-align: right; font-family: monospace; }
        .totals-table .grand-total { font-size: 14px; font-weight: bold; border-top: 2px solid #1e40af; color: #1e40af; }
        .amount-words { margin-top: 10px; padding: 8px 12px; background: #eff6ff; border-left: 3px solid #1e40af; font-style: italic; font-size: 10px; }
        .tax-summary { margin-top: 20px; }
        .tax-summary h3 { font-size: 11px; margin-bottom: 5px; color: #1e40af; }
        .tax-summary table thead th { background: #6b7280; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 10px; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-left { font-size: 8px; color: #888; }
        .qr-code { text-align: right; }
        .qr-code img { width: 80px; height: 80px; }
        .bank-info { margin-top: 15px; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 4px; }
        .bank-info h4 { font-size: 9px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        .notes { margin-top: 15px; padding: 8px 12px; background: #fefce8; border-radius: 4px; }
        .notes h4 { font-size: 9px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="container">
        {{-- HEADER --}}
        <table style="width:100%; margin-bottom: 25px; border: none;">
            <tr>
                <td style="width: 60%; border: none; padding: 0; vertical-align: top;">
                    @if($company->logo_path)
                        <img src="{{ storage_path('app/public/' . $company->logo_path) }}" alt="Logo" style="max-height: 50px; margin-bottom: 8px;">
                    @endif
                    <div class="company-name">{{ $company->company_name }}</div>
                    <p>{{ $company->address_street }}</p>
                    <p>{{ $company->address_postal_code }} {{ $company->address_city }}</p>
                    <p>MF: {{ $company->matricule_fiscale }}</p>
                    @if($company->phone)<p>Tél: {{ $company->phone }}</p>@endif
                    @if($company->email)<p>{{ $company->email }}</p>@endif
                </td>
                <td style="width: 40%; border: none; padding: 0; vertical-align: top; text-align: right;">
                    <div class="oldinvoice-title">FACTURE</div>
                    <div class="oldinvoice-number">N° {{ $oldinvoice->oldinvoice_number }}</div>
                    <span class="status-badge status-{{ $oldinvoice->status->value }}">{{ $oldinvoice->status->label() }}</span>
                    @if($oldinvoice->ref_ttn_val)
                        <p style="margin-top: 8px; font-size: 9px; color: #888;">Réf. TTN: {{ $oldinvoice->ref_ttn_val }}</p>
                    @endif
                </td>
            </tr>
        </table>

        {{-- DATES --}}
        <table style="width:100%; margin-bottom: 20px; border: none;">
            <tr>
                <td style="border: none; padding: 4px 10px; background: #f9fafb; border-radius: 4px;">
                    <span style="font-size: 8px; text-transform: uppercase; color: #888;">Date facture</span><br>
                    <strong>{{ \Carbon\Carbon::parse($oldinvoice->oldinvoice_date)->format('d/m/Y') }}</strong>
                </td>
                @if($oldinvoice->due_date)
                <td style="border: none; padding: 4px 10px; background: #f9fafb; border-radius: 4px;">
                    <span style="font-size: 8px; text-transform: uppercase; color: #888;">Échéance</span><br>
                    <strong>{{ \Carbon\Carbon::parse($oldinvoice->due_date)->format('d/m/Y') }}</strong>
                </td>
                @endif
                <td style="border: none; padding: 4px 10px; background: #f9fafb; border-radius: 4px;">
                    <span style="font-size: 8px; text-transform: uppercase; color: #888;">Type</span><br>
                    <strong>{{ $oldinvoice->document_type_code->label() }}</strong>
                </td>
            </tr>
        </table>

        {{-- CLIENT --}}
        <table style="width:100%; margin-bottom: 20px; border: none;">
            <tr>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                    <div class="info-box-title">ÉMETTEUR</div>
                    <p class="name">{{ $company->company_name }}</p>
                    <p>{{ $company->address_street }}, {{ $company->address_postal_code }} {{ $company->address_city }}</p>
                    <p>MF: {{ $company->matricule_fiscale }}</p>
                </td>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                    <div class="info-box-title">CLIENT</div>
                    <p class="name">{{ $oldinvoice->customer->name }}</p>
                    @if($oldinvoice->customer->address_street)
                        <p>{{ $oldinvoice->customer->address_street }}, {{ $oldinvoice->customer->address_postal_code }} {{ $oldinvoice->customer->address_city }}</p>
                    @endif
                    <p>{{ $oldinvoice->customer->identifier_type->value }}: {{ $oldinvoice->customer->identifier_value }}</p>
                    @if($oldinvoice->customer->matricule_fiscale)
                        <p>MF: {{ $oldinvoice->customer->matricule_fiscale }}</p>
                    @endif
                </td>
            </tr>
        </table>

        {{-- LINES TABLE --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 10%;">Code</th>
                    <th style="width: 28%;">Description</th>
                    <th class="right" style="width: 10%;">Qté</th>
                    <th class="right" style="width: 12%;">P.U. (TND)</th>
                    <th class="right" style="width: 8%;">Remise</th>
                    <th class="right" style="width: 12%;">HT (TND)</th>
                    <th class="right" style="width: 7%;">TVA%</th>
                    <th class="right" style="width: 12%;">TTC (TND)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($oldinvoice->lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="code">{{ $line->item_code }}</td>
                    <td>{{ $line->item_description }}</td>
                    <td class="right">{{ number_format((float)$line->quantity, 3, '.', '') }}</td>
                    <td class="right">{{ number_format((float)$line->unit_price, 3, '.', '') }}</td>
                    <td class="right">{{ number_format((float)$line->discount_rate, 2, '.', '') }}%</td>
                    <td class="right">{{ number_format((float)$line->line_total_ht, 3, '.', '') }}</td>
                    <td class="right">{{ number_format((float)$line->tva_rate, 0) }}%</td>
                    <td class="right"><strong>{{ number_format((float)$line->line_total_ttc, 3, '.', '') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TOTALS --}}
        <table style="width: 280px; margin-left: auto; border: none;">
            <tr><td style="border:none; padding:4px 8px; color:#666;">Total HT</td><td style="border:none; padding:4px 8px; text-align:right; font-family:monospace;">{{ number_format((float)$oldinvoice->total_ht, 3, '.', '') }} TND</td></tr>
            <tr><td style="border:none; padding:4px 8px; color:#666;">Total TVA</td><td style="border:none; padding:4px 8px; text-align:right; font-family:monospace;">{{ number_format((float)$oldinvoice->total_tva, 3, '.', '') }} TND</td></tr>
            <tr><td style="border:none; padding:4px 8px; color:#666;">Timbre fiscal</td><td style="border:none; padding:4px 8px; text-align:right; font-family:monospace;">{{ number_format((float)$oldinvoice->timbre_fiscal, 3, '.', '') }} TND</td></tr>
            <tr class="grand-total">
                <td style="border:none; padding:6px 8px; font-size:14px; font-weight:bold; border-top:2px solid #1e40af; color:#1e40af;">Total TTC</td>
                <td style="border:none; padding:6px 8px; text-align:right; font-family:monospace; font-size:14px; font-weight:bold; border-top:2px solid #1e40af; color:#1e40af;">{{ number_format((float)$oldinvoice->total_ttc, 3, '.', '') }} TND</td>
            </tr>
        </table>

        {{-- AMOUNT IN WORDS --}}
        @if(!empty($amountInWords))
        <div class="amount-words">
            Arrêtée la présente facture à la somme de: <strong>{{ $amountInWords }}</strong>
        </div>
        @endif

        {{-- TAX SUMMARY --}}
        @if($oldinvoice->taxLines->count() > 0)
        <div class="tax-summary">
            <h3>Résumé fiscal</h3>
            <table>
                <thead>
                    <tr>
                        <th>Type taxe</th>
                        <th class="right">Taux</th>
                        <th class="right">Base imposable</th>
                        <th class="right">Montant taxe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($oldinvoice->taxLines as $taxLine)
                    <tr>
                        <td>{{ $taxLine->tax_type_code }}</td>
                        <td class="right">{{ number_format((float)$taxLine->tax_rate, 2) }}%</td>
                        <td class="right">{{ number_format((float)$taxLine->taxable_amount, 3, '.', '') }} TND</td>
                        <td class="right">{{ number_format((float)$taxLine->tax_amount, 3, '.', '') }} TND</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- BANK INFO --}}
        @if($company->bank_name || $company->bank_rib)
        <div class="bank-info">
            <h4>Coordonnées bancaires</h4>
            @if($company->bank_name)<p>Banque: {{ $company->bank_name }}</p>@endif
            @if($company->bank_rib)<p>RIB: {{ $company->bank_rib }}</p>@endif
            @if($company->postal_account)<p>CCP: {{ $company->postal_account }}</p>@endif
        </div>
        @endif

        {{-- NOTES --}}
        @if($oldinvoice->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $oldinvoice->notes }}</p>
        </div>
        @endif

        {{-- FOOTER --}}
        <table style="width:100%; margin-top:30px; border:none; border-top:1px solid #e5e7eb; padding-top:10px;">
            <tr>
                <td style="border:none; padding:10px 0 0 0; vertical-align:bottom;">
                    <p style="font-size:8px; color:#888;">{{ $company->company_name }} — MF: {{ $company->matricule_fiscale }}</p>
                    <p style="font-size:8px; color:#888;">{{ $company->address_street }}, {{ $company->address_postal_code }} {{ $company->address_city }}</p>
                    <p style="font-size:7px; color:#aaa; margin-top:4px;">Document généré le {{ now()->format('d/m/Y à H:i') }} — Novation ERP</p>
                </td>
                <td style="border:none; padding:10px 0 0 0; text-align:right; vertical-align:bottom; width:100px;">
                    @if(!empty($qrCodeBase64))
                        <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code" style="width:80px; height:80px;">
                        <p style="font-size:7px; color:#888; margin-top:2px;">CEV QR Code</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

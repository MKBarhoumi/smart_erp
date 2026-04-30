<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Summary {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .container { padding: 20px 30px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #4f46e5; margin-bottom: 5px; }
        .header p { color: #666; font-size: 11px; }
        .summary-cards { display: flex; gap: 15px; margin-bottom: 20px; }
        .card { flex: 1; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; }
        .card .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .card .value { font-size: 16px; font-weight: bold; margin-top: 4px; }
        .card .value.blue { color: #3b82f6; }
        .card .value.purple { color: #8b5cf6; }
        .card .value.green { color: #10b981; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead th { background: #4f46e5; color: white; padding: 8px 12px; font-size: 9px; text-transform: uppercase; text-align: left; }
        thead th.right { text-align: right; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        tbody td.right { text-align: right; font-family: monospace; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tfoot td { padding: 10px 12px; background: #eef2ff; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tax Summary Report</h1>
            <p>Year: {{ $year }} | Generated: {{ $generatedAt }}</p>
        </div>

        <table style="width:100%; border:none; margin-bottom:20px;">
            <tr>
                <td style="border:none; padding:10px; background:#dbeafe; border-radius:6px; width:33%;">
                    <span style="font-size:9px; color:#1e40af; text-transform:uppercase;">VAT Collected</span><br>
                    <strong style="font-size:16px; color:#1e40af;">{{ number_format((float)$totals['tva'], 3, '.', ' ') }} TND</strong>
                </td>
                <td style="border:none; padding:10px; background:#ede9fe; border-radius:6px; width:33%;">
                    <span style="font-size:9px; color:#5b21b6; text-transform:uppercase;">Stamp Duty</span><br>
                    <strong style="font-size:16px; color:#5b21b6;">{{ number_format((float)$totals['timbre'], 3, '.', ' ') }} TND</strong>
                </td>
                <td style="border:none; padding:10px; background:#d1fae5; border-radius:6px; width:33%;">
                    <span style="font-size:9px; color:#065f46; text-transform:uppercase;">Total Taxes</span><br>
                    <strong style="font-size:16px; color:#065f46;">{{ number_format((float)$totals['total'], 3, '.', ' ') }} TND</strong>
                </td>
            </tr>
        </table>

        <h3 style="margin-bottom:10px; color:#374151;">Quarterly Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Quarter</th>
                    <th class="right">Invoices</th>
                    <th class="right">Taxable Base</th>
                    <th class="right">VAT</th>
                    <th class="right">Stamp</th>
                    <th class="right">Total Taxes</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $quarterLabels = ['Q1 (Jan-Mar)', 'Q2 (Apr-Jun)', 'Q3 (Jul-Sep)', 'Q4 (Oct-Dec)'];
                @endphp
                @foreach($data as $q)
                <tr>
                    <td>{{ $quarterLabels[($q['quarter'] ?? 1) - 1] ?? 'Q?' }}</td>
                    <td class="right">{{ $q['invoice_count'] ?? 0 }}</td>
                    <td class="right">{{ number_format((float)($q['taxable_base'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format((float)($q['tva_collected'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format((float)($q['timbre_fiscal'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right"><strong>{{ number_format((float)($q['total_tax'] ?? 0), 3, '.', ' ') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Yearly Total</strong></td>
                    <td class="right"><strong>{{ $data->sum(fn($q) => $q['invoice_count'] ?? 0) }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['base'], 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['tva'], 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['timbre'], 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['total'], 3, '.', ' ') }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>This report was generated automatically by NovERP. Data is subject to change.</p>
        </div>
    </div>
</body>
</html>

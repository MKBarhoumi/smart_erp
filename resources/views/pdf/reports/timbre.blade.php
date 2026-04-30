<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Stamp Duty Report {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .container { padding: 20px 30px; }
        .header { border-bottom: 2px solid #7c3aed; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #7c3aed; margin-bottom: 5px; }
        .header p { color: #666; font-size: 11px; }
        .summary { background: #ede9fe; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .summary .label { font-size: 9px; color: #5b21b6; text-transform: uppercase; }
        .summary .value { font-size: 22px; font-weight: bold; color: #5b21b6; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead th { background: #7c3aed; color: white; padding: 8px 12px; font-size: 9px; text-transform: uppercase; text-align: left; }
        thead th.right { text-align: right; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        tbody td.right { text-align: right; font-family: monospace; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tfoot td { padding: 10px 12px; background: #ede9fe; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Stamp Duty Report</h1>
            <p>Year: {{ $year }} | Generated: {{ $generatedAt }}</p>
        </div>

        <div class="summary">
            <div class="label">Total Stamp Duty {{ $year }}</div>
            <div class="value">{{ number_format((float)$yearlyTotal, 3, '.', ' ') }} TND</div>
        </div>

        @php
            $monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        @endphp

        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="right">Invoices</th>
                    <th class="right">Stamp Duty (TND)</th>
                    <th class="right">Avg per Invoice</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyData as $row)
                @php
                    $monthIndex = ($row['month'] ?? $row->month ?? 1) - 1;
                    $count = (int)($row['invoice_count'] ?? $row->invoice_count ?? 0);
                    $total = (float)($row['total_timbre'] ?? $row->total_timbre ?? 0);
                    $avg = $count > 0 ? $total / $count : 0;
                @endphp
                <tr>
                    <td>{{ $monthNames[$monthIndex] ?? 'Unknown' }}</td>
                    <td class="right">{{ $count }}</td>
                    <td class="right">{{ number_format($total, 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format($avg, 3, '.', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total</strong></td>
                    <td class="right"><strong>{{ $monthlyData->sum(fn($r) => (int)($r['invoice_count'] ?? $r->invoice_count ?? 0)) }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$yearlyTotal, 3, '.', ' ') }} TND</strong></td>
                    <td class="right">-</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>Stamp duty (Timbre Fiscal) is a tax applied to commercial invoices in Tunisia at a fixed rate of 1 TND per applicable invoice.</p>
            <p>This report was generated automatically by NovERP.</p>
        </div>
    </div>
</body>
</html>

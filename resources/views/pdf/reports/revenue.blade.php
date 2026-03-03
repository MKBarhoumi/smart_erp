<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Revenue Report {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .container { padding: 20px 30px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #4f46e5; margin-bottom: 5px; }
        .header p { color: #666; font-size: 11px; }
        .summary-box { background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; }
        .summary-item h3 { font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .summary-item .value { font-size: 18px; font-weight: bold; color: #4f46e5; }
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
            <h1>Revenue Report</h1>
            <p>Year: {{ $year }} | Generated: {{ $generatedAt }}</p>
        </div>

        <table style="width: auto; border: none; margin-bottom: 20px;">
            <tr>
                <td style="border: none; padding: 10px 15px; background: #f3f4f6; border-radius: 8px;">
                    <span style="font-size: 9px; color: #666; text-transform: uppercase;">Yearly Total</span><br>
                    <strong style="font-size: 16px; color: #4f46e5;">{{ number_format((float)$yearlyTotal, 3, '.', ' ') }} TND</strong>
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="right">Invoices</th>
                    <th class="right">Revenue (TND)</th>
                    <th class="right">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @php $yearlyTotalFloat = (float)$yearlyTotal ?: 1; @endphp
                @foreach($data as $row)
                <tr>
                    <td>{{ $row['month'] ?? $row->month ?? 'N/A' }}</td>
                    <td class="right">{{ $row['count'] ?? $row->count ?? 0 }}</td>
                    <td class="right">{{ number_format((float)($row['total'] ?? $row->total ?? 0), 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format(((float)($row['total'] ?? $row->total ?? 0) / $yearlyTotalFloat) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total</strong></td>
                    <td class="right"><strong>{{ $data->sum(fn($r) => $r['count'] ?? $r->count ?? 0) }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$yearlyTotal, 3, '.', ' ') }} TND</strong></td>
                    <td class="right"><strong>100%</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>This report was generated automatically by NovERP. Data is subject to change.</p>
        </div>
    </div>
</body>
</html>

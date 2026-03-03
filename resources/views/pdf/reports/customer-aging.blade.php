<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Customer Aging Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; line-height: 1.4; }
        .container { padding: 20px 25px; }
        .header { border-bottom: 2px solid #ef4444; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #ef4444; margin-bottom: 5px; }
        .header p { color: #666; font-size: 11px; }
        .summary { background: #fef2f2; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .summary strong { color: #dc2626; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #dc2626; color: white; padding: 6px 8px; font-size: 8px; text-transform: uppercase; text-align: left; }
        thead th.right { text-align: right; }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
        tbody td.right { text-align: right; font-family: monospace; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr.over-90 { background: #fef2f2; }
        tfoot td { padding: 8px; background: #fef2f2; font-weight: bold; }
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 7px; color: #888; }
        .aging-legend { margin-top: 15px; padding: 10px; background: #f9fafb; border-radius: 4px; font-size: 8px; }
        .aging-legend span { margin-right: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Customer Aging Report</h1>
            <p>Generated: {{ $generatedAt }}</p>
        </div>

        <div class="summary">
            Total Outstanding: <strong>{{ number_format((float)$totals['total_outstanding'], 3, '.', ' ') }} TND</strong>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:25%">Customer</th>
                    <th style="width:15%">Identifier</th>
                    <th class="right" style="width:12%">Current<br>(0-30)</th>
                    <th class="right" style="width:12%">30-60<br>Days</th>
                    <th class="right" style="width:12%">60-90<br>Days</th>
                    <th class="right" style="width:12%">Over 90<br>Days</th>
                    <th class="right" style="width:12%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $c)
                <tr @if((float)($c['over_90'] ?? 0) > 0) class="over-90" @endif>
                    <td>{{ $c['name'] }}</td>
                    <td style="font-family: monospace; font-size: 7px;">{{ $c['identifier_value'] }}</td>
                    <td class="right">{{ number_format((float)($c['current'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format((float)($c['days_30_60'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format((float)($c['days_60_90'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right" @if((float)($c['over_90'] ?? 0) > 0) style="color:#dc2626; font-weight:bold;" @endif>{{ number_format((float)($c['over_90'] ?? 0), 3, '.', ' ') }}</td>
                    <td class="right"><strong>{{ number_format((float)($c['total_outstanding'] ?? 0), 3, '.', ' ') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['current'], 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['days_30_60'], 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['days_60_90'], 3, '.', ' ') }}</strong></td>
                    <td class="right" style="color:#dc2626;"><strong>{{ number_format((float)$totals['over_90'], 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)$totals['total_outstanding'], 3, '.', ' ') }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="aging-legend">
            <strong>Aging Buckets:</strong>
            <span>Current: 0-30 days</span>
            <span>30-60: 31-60 days</span>
            <span>60-90: 61-90 days</span>
            <span style="color:#dc2626;">Over 90: 90+ days (high risk)</span>
        </div>

        <div class="footer">
            <p>This report was generated automatically by NovERP. Amounts marked in red indicate overdue receivables requiring attention.</p>
        </div>
    </div>
</body>
</html>

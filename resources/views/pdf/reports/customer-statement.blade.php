<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Customer Statement - {{ $customer->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .container { padding: 20px 30px; }
        .header { border-bottom: 2px solid #0891b2; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #0891b2; margin-bottom: 5px; }
        .header p { color: #666; font-size: 11px; }
        .customer-info { background: #ecfeff; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .customer-info h3 { color: #0e7490; font-size: 14px; margin-bottom: 5px; }
        .customer-info p { margin: 2px 0; }
        .summary-cards { margin-bottom: 20px; }
        .summary-cards table { border: none; }
        .summary-cards td { border: none; padding: 10px; background: #f9fafb; text-align: center; }
        .summary-cards td.positive { background: #d1fae5; }
        .summary-cards td.negative { background: #fee2e2; }
        .summary-cards .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .summary-cards .value { font-size: 16px; font-weight: bold; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead th { background: #0891b2; color: white; padding: 6px 10px; font-size: 8px; text-transform: uppercase; text-align: left; }
        thead th.right { text-align: right; }
        tbody td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        tbody td.right { text-align: right; font-family: monospace; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tfoot td { padding: 8px 10px; background: #ecfeff; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; font-size: 8px; color: #888; }
        .status { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Account Statement</h1>
            <p>Generated: {{ $generatedAt }}</p>
        </div>

        <div class="customer-info">
            <h3>{{ $customer->name }}</h3>
            <p><strong>ID:</strong> {{ $customer->identifier_value }}</p>
            @if($customer->street)<p>{{ $customer->street }}</p>@endif
            @if($customer->city || $customer->postal_code)<p>{{ $customer->postal_code }} {{ $customer->city }}</p>@endif
            @if($customer->email)<p>{{ $customer->email }}</p>@endif
        </div>

        <div class="summary-cards">
            <table style="width:100%;">
                <tr>
                    <td style="width:33%;">
                        <div class="label">Total Invoiced</div>
                        <div class="value">{{ number_format((float)($totals['total_invoiced'] ?? 0), 3, '.', ' ') }} TND</div>
                    </td>
                    <td style="width:33%;" class="positive">
                        <div class="label">Total Paid</div>
                        <div class="value" style="color:#059669;">{{ number_format((float)($totals['total_paid'] ?? 0), 3, '.', ' ') }} TND</div>
                    </td>
                    <td style="width:33%;" @if((float)($totals['balance'] ?? 0) > 0) class="negative" @endif>
                        <div class="label">Balance Due</div>
                        <div class="value" @if((float)($totals['balance'] ?? 0) > 0) style="color:#dc2626;" @endif>{{ number_format((float)($totals['balance'] ?? 0), 3, '.', ' ') }} TND</div>
                    </td>
                </tr>
            </table>
        </div>

        <h3 style="margin-bottom:10px; color:#374151;">Transaction History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice #</th>
                    <th>Due Date</th>
                    <th class="right">Amount</th>
                    <th class="right">Paid</th>
                    <th class="right">Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                @php
                    $paid = $inv->payments->sum('amount');
                    $balance = (float)$inv->total_ttc - (float)$paid;
                    $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
                @endphp
                <tr>
                    <td>{{ $inv->oldinvoice_date }}</td>
                    <td>{{ $inv->oldinvoice_number }}</td>
                    <td>{{ $inv->due_date ?? '—' }}</td>
                    <td class="right">{{ number_format((float)$inv->total_ttc, 3, '.', ' ') }}</td>
                    <td class="right">{{ number_format((float)$paid, 3, '.', ' ') }}</td>
                    <td class="right" @if($balance > 0) style="color:#dc2626; font-weight:bold;" @endif>{{ number_format($balance, 3, '.', ' ') }}</td>
                    <td>
                        <span class="status status-{{ $status }}">{{ $status }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>TOTAL</strong></td>
                    <td class="right"><strong>{{ number_format((float)($totals['total_invoiced'] ?? 0), 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)($totals['total_paid'] ?? 0), 3, '.', ' ') }}</strong></td>
                    <td class="right"><strong>{{ number_format((float)($totals['balance'] ?? 0), 3, '.', ' ') }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>This statement is for informational purposes. Please contact us for any discrepancies.</p>
            <p>Generated by NovERP.</p>
        </div>
    </div>
</body>
</html>

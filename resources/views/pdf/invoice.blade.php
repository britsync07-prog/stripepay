@php
    $fmt = fn ($minor) => \App\Models\Invoice::formatMoneyMinorExact((int) $minor, $invoice->currency);
    $refunded = $invoice->status->value === 'refunded';
    $logo = public_path('assets/images/logoBA-ink.png');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .gold-rule { height: 3px; background: #a8791f; }
        .wrap { padding: 44px 48px; }
        .serif { font-family: 'DejaVu Serif', serif; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; }
        .muted { color: #6b7280; }
        .eyebrow { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #6b7280; }
        h1.ref { margin: 2px 0 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .right { text-align: right; }

        .hero { margin-top: 26px; background: #f7f5f1; border-top: 1px solid #ecece8; border-bottom: 1px solid #ecece8; }
        .hero td { padding: 18px 48px; }
        .amount { font-size: 30px; font-weight: bold; }
        .pill { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .pill-paid { background: #dcfce7; color: #15803d; }
        .pill-refunded { background: #fee2e2; color: #b91c1c; }

        .section { padding: 0 48px; }
        .label { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #6b7280; }

        table.items { margin-top: 8px; }
        table.items th { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #6b7280; text-align: left; border-bottom: 1px solid #e5e7eb; padding: 6px 8px; }
        table.items td { padding: 10px 8px; border-bottom: 1px solid #f1f1ee; }

        .totals { width: 240px; margin-left: auto; margin-top: 16px; }
        .totals td { padding: 4px 8px; }
        .totals .grand td { border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .grand .lbl { font-family: 'DejaVu Serif', serif; font-size: 14px; font-weight: bold; }
        .grand .val { font-family: 'DejaVu Sans Mono', monospace; font-size: 15px; font-weight: bold; color: #a8791f; }

        .footer { margin-top: 34px; padding: 16px 48px; background: #faf9f6; border-top: 1px solid #ecece8; color: #6b7280; font-size: 11px; }
    </style>
</head>
<body>
    <div class="gold-rule"></div>

    <div class="wrap" style="padding-bottom: 0;">
        <table>
            <tr>
                <td style="width: 60%;">
                    @if (file_exists($logo))
                        <img src="{{ $logo }}" style="height: 34px;" alt="{{ $company['name'] }}">
                    @else
                        <span class="serif" style="font-size: 18px; font-weight: bold;">{{ $company['name'] }}</span>
                    @endif
                </td>
                <td class="right">
                    <div class="eyebrow">Invoice</div>
                    <h1 class="ref mono">{{ $invoice->reference }}</h1>
                </td>
            </tr>
        </table>
    </div>

    <table class="hero">
        <tr>
            <td style="width: 60%;">
                <div class="eyebrow">{{ $refunded ? 'Amount refunded' : 'Amount paid' }}</div>
                <div class="serif amount">{{ $fmt($invoice->total) }}</div>
            </td>
            <td class="right">
                <span class="pill {{ $refunded ? 'pill-refunded' : 'pill-paid' }}">
                    {{ $refunded ? 'Refunded' : 'Paid'.($invoice->paid_at ? ' on '.$invoice->paid_at->toFormattedDayDateString() : '') }}
                </span>
            </td>
        </tr>
    </table>

    <div class="section" style="padding-top: 26px;">
        <table>
            <tr>
                <td style="width: 55%;">
                    <div class="label">Billed to</div>
                    <div style="margin-top: 6px; font-weight: bold;">{{ $invoice->client?->name ?? '—' }}</div>
                    @if ($invoice->client?->email)
                        <div class="muted">{{ $invoice->client->email }}</div>
                    @endif
                    @if ($invoice->consultationRequest)
                        <div class="mono muted" style="font-size: 11px;">{{ $invoice->consultationRequest->reference }}</div>
                    @endif
                </td>
                <td class="right">
                    <div style="margin-bottom: 3px;"><span class="muted">Issued&nbsp;&nbsp;</span> {{ $invoice->issued_at?->toFormattedDayDateString() ?? '—' }}</div>
                    <div><span class="muted">From&nbsp;&nbsp;</span> {{ $company['name'] }}</div>
                    @if ($company['vat'])
                        <div><span class="muted">Tax ID&nbsp;&nbsp;</span> {{ $company['vat'] }}</div>
                    @endif
                    @if ($company['address'])
                        <div class="muted" style="margin-top: 3px; white-space: pre-line;">{{ $company['address'] }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section" style="margin-top: 22px;">
        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td style="font-weight: bold;">{{ $item->description }}</td>
                        <td class="right mono muted">{{ $item->quantity }}</td>
                        <td class="right mono muted">{{ $fmt($item->unit_amount) }}</td>
                        <td class="right mono" style="font-weight: bold;">{{ $fmt($item->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="muted">Subtotal</td>
                <td class="right mono">{{ $fmt($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td class="muted">Tax ({{ rtrim(rtrim((string) $invoice->tax_rate, '0'), '.') }}%)</td>
                <td class="right mono">{{ $fmt($invoice->tax_amount) }}</td>
            </tr>
            <tr class="grand">
                <td class="lbl">Total</td>
                <td class="right val">{{ $fmt($invoice->total) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        @if ($invoice->notes)
            {{ $invoice->notes }}
        @elseif ($company['footer'])
            {{ $company['footer'] }}
        @else
            Thank you for working with {{ $company['name'] }}. Questions about this invoice? Reply to your engagement thread and our team will help.
        @endif
    </div>
</body>
</html>

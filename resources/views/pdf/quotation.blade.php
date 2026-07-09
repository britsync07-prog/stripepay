@php
    $logo = public_path('assets/images/logoBA-ink.png');
    $by = $quotation->preparedBy;
    $for = $quotation->preparedFor;
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
        .label { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #6b7280; }

        .section { padding: 0 48px; }
        .meta td { padding: 2px 0; }

        .parties { margin-top: 26px; background: #f7f5f1; border-top: 1px solid #ecece8; border-bottom: 1px solid #ecece8; }
        .parties td { padding: 18px 48px; width: 50%; }

        h2.title { font-family: 'DejaVu Serif', serif; font-size: 18px; margin: 0 0 4px; }

        ul.scope { margin: 8px 0 0; padding-left: 0; list-style: none; }
        ul.scope li { padding: 5px 0; border-bottom: 1px solid #f1f1ee; }
        ul.scope li:before { content: '\2713'; color: #a8791f; font-weight: bold; margin-right: 8px; }

        table.items { margin-top: 8px; }
        table.items th { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #6b7280; text-align: left; border-bottom: 1px solid #e5e7eb; padding: 6px 8px; }
        table.items td { padding: 10px 8px; border-bottom: 1px solid #f1f1ee; }

        .totals { width: 260px; margin-left: auto; margin-top: 16px; }
        .totals td { padding: 4px 8px; }
        .totals .grand td { border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .grand .lbl { font-family: 'DejaVu Serif', serif; font-size: 14px; font-weight: bold; }
        .grand .val { font-family: 'DejaVu Sans Mono', monospace; font-size: 15px; font-weight: bold; color: #a8791f; }

        .terms { margin-top: 8px; }
        .terms h3 { font-family: 'DejaVu Serif', serif; font-size: 13px; margin: 20px 0 6px; }
        .terms p, .terms li { color: #374151; font-size: 11.5px; line-height: 1.5; }
        .terms ul { margin: 4px 0; padding-left: 18px; }

        .sign { margin-top: 26px; }
        .footer { margin-top: 30px; padding: 16px 48px; background: #faf9f6; border-top: 1px solid #ecece8; color: #6b7280; font-size: 11px; }
    </style>
</head>
<body>
    <div class="gold-rule"></div>

    <div class="wrap" style="padding-bottom: 0;">
        <table>
            <tr>
                <td style="width: 55%;">
                    @if (file_exists($logo))
                        <img src="{{ $logo }}" style="height: 34px;" alt="{{ $by->name }}">
                    @else
                        <span class="serif" style="font-size: 18px; font-weight: bold;">{{ $by->name }}</span>
                    @endif
                </td>
                <td class="right">
                    <div class="eyebrow">Quotation</div>
                    <h1 class="ref mono">{{ $quotation->number }}</h1>
                    <table class="meta" style="margin-top: 8px; width: auto; float: right;">
                        <tr><td class="muted right" style="padding-right: 10px;">Date</td><td class="right">{{ $quotation->issuedAt ?? '—' }}</td></tr>
                        <tr><td class="muted right" style="padding-right: 10px;">Validity</td><td class="right">{{ $quotation->validityDays !== null ? $quotation->validityDays.' days' : '—' }}</td></tr>
                        @if ($quotation->validUntil)
                            <tr><td class="muted right" style="padding-right: 10px;">Valid until</td><td class="right">{{ $quotation->validUntil }}</td></tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="parties">
        <tr>
            <td>
                <div class="label">Prepared by</div>
                <div style="margin-top: 6px; font-weight: bold;">{{ $by->name }}</div>
                @if ($by->country)<div class="muted">{{ $by->country }}</div>@endif
                @if ($by->email)<div class="muted">{{ $by->email }}</div>@endif
            </td>
            <td>
                <div class="label">Prepared for</div>
                <div style="margin-top: 6px; font-weight: bold;">{{ $for->name ?? '—' }}</div>
                @if ($for->company)<div class="muted">{{ $for->company }}</div>@endif
                @if ($for->email)<div class="muted">{{ $for->email }}</div>@endif
                @if ($for->country)<div class="muted">{{ $for->country }}</div>@endif
            </td>
        </tr>
    </table>

    <div class="section" style="padding-top: 26px;">
        <h2 class="title">{{ $quotation->title }}</h2>

        @if (count($quotation->scopeItems))
            <div class="label" style="margin-top: 16px;">Scope of services</div>
            <ul class="scope">
                @foreach ($quotation->scopeItems as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="section" style="margin-top: 22px;">
        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quotation->lineItems as $item)
                    <tr>
                        <td style="font-weight: bold;">{{ $item->description }}</td>
                        <td class="right mono muted">{{ $item->quantity }}</td>
                        <td class="right mono muted">{{ $item->unitPrice }}</td>
                        <td class="right mono" style="font-weight: bold;">{{ $item->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="muted">Subtotal</td>
                <td class="right mono">{{ $quotation->subtotal }}</td>
            </tr>
            <tr>
                <td class="muted">Taxes</td>
                <td class="right mono">{{ $quotation->taxLabel }}</td>
            </tr>
            <tr class="grand">
                <td class="lbl">Total Due</td>
                <td class="right val">{{ $quotation->total }}</td>
            </tr>
        </table>
    </div>

    <div class="section terms">
        @if ($quotation->deliveryTimeline)
            <h3>Delivery timeline</h3>
            <p>{{ $quotation->deliveryTimeline }}</p>
        @endif

        <h3>Payment terms</h3>
        <ul>
            <li>100% payment upon acceptance of this quotation.</li>
            <li>Payment is processed through the secure {{ $by->name }} payment platform.</li>
            <li>Work begins once payment has been successfully received.</li>
        </ul>

        <h3>Acceptance</h3>
        <p>By confirming this quotation and completing the payment, the client authorizes
            {{ $by->name }} to commence {{ $quotation->title }} under the terms outlined above.</p>

        @if ($by->signatoryName)
            <div class="sign">
                <div class="label">Prepared by</div>
                <div style="margin-top: 6px; font-weight: bold;">{{ $by->signatoryName }}</div>
                @if ($by->signatoryTitle)<div class="muted">{{ $by->signatoryTitle }}</div>@endif
                <div class="muted">{{ $by->name }}</div>
            </div>
        @endif
    </div>

    <div class="footer">
        This quotation is valid for {{ $quotation->validityDays !== null ? $quotation->validityDays.' days' : 'the stated period' }} from the date of issue.
        Questions? Reply to the email this quotation was sent from and our team will help.
    </div>
</body>
</html>

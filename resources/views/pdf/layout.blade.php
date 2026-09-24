<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 20px; margin: 0; color: #4338ca; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .header td { vertical-align: top; }
        .issuer-name { font-size: 14px; font-weight: bold; }
        .meta td { padding: 1px 0; }
        .box { margin-top: 18px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .lines { margin-top: 18px; }
        .lines th { background: #4338ca; color: #ffffff; font-weight: bold; padding: 6px; text-align: left; font-size: 9px; }
        .lines td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
        .lines th.right, .lines td.right { text-align: right; }
        .totals { width: 45%; margin-left: 55%; margin-top: 12px; }
        .totals td { padding: 3px 6px; }
        .totals .grand td { border-top: 2px solid #1e293b; font-weight: bold; font-size: 12px; padding-top: 6px; }
        .notes { margin-top: 18px; white-space: pre-line; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 55%">
                <div class="issuer-name">{{ config('invoicing.issuer.name') }}</div>
                <div class="muted">{{ config('invoicing.issuer.address') }}</div>
                <div class="muted">TRN {{ config('invoicing.issuer.tax_number') }}</div>
                <div class="muted">{{ config('invoicing.issuer.email') }}</div>
            </td>
            <td class="right">
                <h1>{{ $heading }}</h1>
                @yield('meta')
            </td>
        </tr>
    </table>

    @yield('content')

    <div class="footer">
        {{ config('invoicing.issuer.name') }} · TRN {{ config('invoicing.issuer.tax_number') }} · {{ config('invoicing.issuer.website') }}
    </div>
</body>
</html>

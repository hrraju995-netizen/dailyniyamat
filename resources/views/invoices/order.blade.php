<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Invoice {{ $order->number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        color: #1A1A1A;
        background: #fff;
        padding: 30px 36px;
    }
    .header {
        width: 100%;
        border-bottom: 2.5px solid #2E7D32;
        padding-bottom: 16px;
        margin-bottom: 22px;
    }
    .brand {
        font-size: 24px;
        font-weight: 700;
        color: #2E7D32;
        letter-spacing: -0.5px;
    }
    .brand-tagline {
        font-size: 10px;
        color: #666;
        margin-top: 3px;
    }
    .invoice-title {
        font-size: 24px;
        font-weight: 800;
        color: #111;
        letter-spacing: 0.05em;
    }
    .meta-grid {
        width: 100%;
        margin-bottom: 22px;
        border-collapse: collapse;
    }
    .meta-grid td {
        vertical-align: top;
        padding: 0;
        width: 50%;
    }
    .info-block {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        padding: 12px 16px;
    }
    .section-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #6B7280;
        margin-bottom: 6px;
    }
    .section-value {
        font-size: 12.5px;
        color: #374151;
        line-height: 1.5;
    }
    .badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 10.5px;
        font-weight: 700;
    }
    .badge-green { background: #E8F5E9; color: #1B5E20; }
    .badge-orange { background: #FFF3E0; color: #E65100; }
    .badge-blue { background: #E3F2FD; color: #0D47A1; }
    .badge-red { background: #FEE2E2; color: #991B1B; }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .items-table th {
        background: #F3F4F6;
        padding: 9px 12px;
        text-align: left;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #4B5563;
        border-bottom: 1.5px solid #D1D5DB;
    }
    .items-table th.right { text-align: right; }
    .items-table th.center { text-align: center; }
    .items-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #E5E7EB;
        color: #1F2937;
        font-size: 12px;
    }
    .items-table td.right { text-align: right; }
    .items-table td.center { text-align: center; color: #6B7280; }
    .items-table tr:last-child td { border-bottom: none; }
    .item-thumb {
        width: 38px;
        height: 38px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #E5E7EB;
        vertical-align: middle;
    }
    .item-name { font-weight: 700; color: #111827; }
    .item-weight { font-size: 11px; color: #6B7280; margin-top: 2px; }

    .totals-table {
        width: 290px;
        margin-left: auto;
        border-collapse: collapse;
    }
    .totals-table td {
        padding: 5px 8px;
        font-size: 12.5px;
        color: #374151;
    }
    .totals-table td.label { color: #6B7280; }
    .totals-table td.value { text-align: right; font-weight: 600; color: #111827; }
    .total-row td {
        padding-top: 10px;
        font-size: 15px;
        font-weight: 800;
        color: #111827;
        border-top: 2px solid #2E7D32;
    }
    .total-row td.value { color: #2E7D32; font-size: 16px; }
    .discount-row td { color: #2E7D32; }

    .footer {
        margin-top: 36px;
        padding-top: 14px;
        border-top: 1px solid #E5E7EB;
        font-size: 10.5px;
        color: #6B7280;
        text-align: center;
        line-height: 1.5;
    }
    .divider {
        border: none;
        border-top: 1px solid #E5E7EB;
        margin: 18px 0;
    }
</style>
</head>
<body>

@php
    $siteLogoPath = \App\Models\Setting::get('site_logo');
    $logoBase64   = null;
    if ($siteLogoPath) {
        $fullPath = storage_path('app/public/' . $siteLogoPath);
        if (file_exists($fullPath)) {
            $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : ($ext === 'svg' ? 'image/svg+xml' : 'image/jpeg');
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
        }
    }
    $siteName    = \App\Models\Setting::get('site_name') ?: 'Glade Systems';
    $siteTagline = \App\Models\Setting::get('site_tagline') ?: '';
    $siteEmail   = \App\Models\Setting::get('contact_email') ?: 'support@gladesystems.com';
    $sitePhone   = \App\Models\Setting::get('contact_phone') ?: '';
@endphp

{{-- Header --}}
<div class="header">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: middle; text-align: left;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="{{ $siteName }}" style="max-height: 48px; max-width: 200px;">
                @else
                    <div class="brand">{{ $siteName }}</div>
                    @if($siteTagline)<div class="brand-tagline">{{ $siteTagline }}</div>@endif
                @endif
            </td>
            <td style="vertical-align: middle; text-align: right;">
                <div class="invoice-title">INVOICE</div>
                <div style="font-size: 13px; font-weight: 700; color: #4B5563; margin-top: 4px;">{{ $order->number }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- Order Meta: Customer + Order Info --}}
<table class="meta-grid">
    <tr>
        <td style="width: 50%; padding-right: 10px;">
            <div class="info-block">
                <div class="section-label">Bill To</div>
                <div class="section-value">
                    <strong>{{ $order->customer_name }}</strong><br>
                    {{ $order->address_line }}<br>
                    @if($order->thana){{ $order->thana }}, @endif{{ $order->city }}<br>
                    Phone: {{ $order->customer_phone }}<br>
                    @if($order->customer_email)Email: {{ $order->customer_email }}@endif
                </div>
            </div>
        </td>
        <td style="width: 50%; padding-left: 10px;">
            <div class="info-block">
                <div class="section-label">Order Details</div>
                <div class="section-value">
                    <strong>Date:</strong> {{ $order->placed_at ? $order->placed_at->format('d M Y') : $order->created_at->format('d M Y') }}<br>
                    <strong>Status:</strong>
                    <span class="badge badge-{{ in_array($order->status, ['delivered']) ? 'green' : (in_array($order->status, ['shipped','processing','confirmed']) ? 'blue' : ($order->status === 'cancelled' ? 'red' : 'orange')) }}">
                        {{ ucfirst($order->status) }}
                    </span><br>
                    <strong>Payment Method:</strong>
                    @switch($order->payment_method)
                        @case('cod') Cash on Delivery @break
                        @case('bkash') bKash @break
                        @case('nagad') Nagad @break
                        @case('rocket') Rocket @break
                        @case('sslcommerz') Cards &amp; Mobile Banking (SSLCommerz) @break
                        @default {{ ucfirst($order->payment_method) }}
                    @endswitch
                    &nbsp;<span class="badge badge-{{ $order->payment_status === 'paid' ? 'green' : 'orange' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span><br>
                    @if($order->payment_ref)
                        <strong>Trx Ref:</strong> {{ $order->payment_ref }}<br>
                    @endif
                    @if($order->coupon_code)
                        <strong>Coupon:</strong> {{ $order->coupon_code }}<br>
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- Items --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 25px;">#</th>
            <th style="width: 48px; text-align: center;">Item</th>
            <th>Description</th>
            <th class="center" style="width: 50px;">Qty</th>
            <th class="right" style="width: 90px;">Unit Price</th>
            <th class="right" style="width: 90px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $i => $item)
            @php
                $itemImgBase64 = null;
                $imgPath = null;
                if ($item->product && $item->product->image) {
                    $imgPath = $item->product->image;
                } elseif ($item->combo && $item->combo->image) {
                    $imgPath = $item->combo->image;
                }

                if ($imgPath) {
                    $cleanImg = ltrim(preg_replace('#^storage/#', '', $imgPath), '/');
                    $fullImgPath = storage_path('app/public/' . $cleanImg);
                    if (file_exists($fullImgPath)) {
                        $ext  = strtolower(pathinfo($fullImgPath, PATHINFO_EXTENSION));
                        $mime = match($ext) {
                            'png'  => 'image/png',
                            'webp' => 'image/webp',
                            'gif'  => 'image/gif',
                            default => 'image/jpeg',
                        };
                        $itemImgBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullImgPath));
                    }
                }
            @endphp
            <tr>
                <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
                <td style="text-align: center; vertical-align: middle;">
                    @if($itemImgBase64)
                        <img src="{{ $itemImgBase64 }}" class="item-thumb" alt="">
                    @else
                        <div style="width: 38px; height: 38px; background: #F3F4F6; border-radius: 4px; display: inline-block; line-height: 38px; color: #9CA3AF; font-size: 10px; text-align: center;">N/A</div>
                    @endif
                </td>
                <td>
                    <div class="item-name">{{ $item->name }}</div>
                    @if($item->weight)<div class="item-weight">Variant: {{ $item->weight }}</div>@endif
                </td>
                <td class="center">{{ $item->qty }}</td>
                <td class="right">Tk {{ number_format($item->price) }}</td>
                <td class="right" style="font-weight:700;">Tk {{ number_format($item->line_total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<table class="totals-table">
    <tr>
        <td class="label">Subtotal</td>
        <td class="value">Tk {{ number_format($order->subtotal) }}</td>
    </tr>
    <tr>
        <td class="label">Delivery Fee</td>
        <td class="value">{{ $order->delivery == 0 ? 'Free' : 'Tk ' . number_format($order->delivery) }}</td>
    </tr>
    @if($order->discount > 0)
        <tr class="discount-row">
            <td class="label">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</td>
            <td class="value">−Tk {{ number_format($order->discount) }}</td>
        </tr>
    @endif
    <tr class="total-row">
        <td class="label">Grand Total</td>
        <td class="value">Tk {{ number_format($order->total) }}</td>
    </tr>
</table>

@if($order->notes)
    <hr class="divider">
    <div style="font-size:11.5px;color:#4B5563;">
        <strong>Customer Note:</strong> {{ $order->notes }}
    </div>
@endif

{{-- Footer --}}
<div class="footer">
    <p>Thank you for shopping with <strong>{{ $siteName }}</strong></p>
    <p style="margin-top:3px;">This is a computer-generated invoice and does not require a physical signature.</p>
    <p style="margin-top:3px;">Questions? Contact us at {{ $siteEmail }} @if($sitePhone) | {{ $sitePhone }}@endif</p>
</div>

</body>
</html>

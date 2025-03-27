<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Purchase Order #{{ $purchaseOrder->po_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #333;
        }

        .info-section {
            width: 100%;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .info-column {
            float: left;
            width: 50%;
        }

        .info-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 5px;
            border-radius: 4px;
        }

        .info-box h3 {
            margin-top: 0;
            margin-bottom: 5px;
            color: #333;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background-color: #f3f3f3;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }

        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .totals {
            width: 50%;
            float: right;
        }

        .totals table {
            width: 100%;
        }

        .totals table td {
            border: none;
        }

        .totals table td:first-child {
            text-align: right;
            font-weight: bold;
            width: 70%;
        }

        .totals table td:last-child {
            text-align: right;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .notes {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <h2>{{ $purchaseOrder->po_number }}</h2>
    </div>

    <div class="info-section">
        <div class="info-column">
            <div class="info-box">
                <h3>VENDOR</h3>
                <div>{{ $purchaseOrder->vendor->name }}</div>
                <div>{{ $purchaseOrder->vendor->address }}</div>
                <div>{{ $purchaseOrder->vendor->city }}, {{ $purchaseOrder->vendor->province }}
                    {{ $purchaseOrder->vendor->postal_code }}</div>
                <div>Phone: {{ $purchaseOrder->vendor->phone }}</div>
                <div>Email: {{ $purchaseOrder->vendor->email }}</div>
            </div>
        </div>
        <div class="info-column">
            <div class="info-box">
                <h3>ORDER INFO</h3>
                <div><strong>Order Date:</strong> {{ $purchaseOrder->order_date->format('M d, Y') }}</div>
                <div><strong>Expected Delivery:</strong>
                    {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('M d, Y') : 'Not specified' }}
                </div>
                <div><strong>Status:</strong> {{ ucfirst($purchaseOrder->status) }}</div>
                <div><strong>Payment Status:</strong> {{ ucfirst($purchaseOrder->payment_status) }}</div>
            </div>
            <div class="info-box">
                <h3>SHIPPING ADDRESS</h3>
                <div>{{ $purchaseOrder->shipping_address ?: 'Same as company address' }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Tax</th>
                <th>Discount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->items as $item)
                <tr>
                    <td>{{ $item->inventory->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }} {{ $item->unit }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->tax_rate }}% (${{ number_format($item->tax_amount, 2) }})</td>
                    <td>{{ $item->discount_percent }}% (${{ number_format($item->discount_amount, 2) }})</td>
                    <td>${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($purchaseOrder->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Tax Amount:</td>
                <td>${{ number_format($purchaseOrder->tax_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Discount Amount:</td>
                <td>${{ number_format($purchaseOrder->discount_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Amount:</td>
                <td>${{ number_format($purchaseOrder->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    @if ($purchaseOrder->notes)
        <div class="notes">
            <h3>Notes:</h3>
            <p>{{ $purchaseOrder->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your business.</p>
        <p>Generated on {{ now()->format('M d, Y h:i A') }}</p>
    </div>
</body>

</html>

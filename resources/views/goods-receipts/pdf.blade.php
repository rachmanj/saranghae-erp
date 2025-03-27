<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Goods Receipt #{{ $goodsReceipt->receipt_number }}</title>
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

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #666;
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
        <h1>GOODS RECEIPT</h1>
        <h2>{{ $goodsReceipt->receipt_number }}</h2>
    </div>

    <div class="info-section">
        <div class="info-column">
            <div class="info-box">
                <h3>VENDOR</h3>
                <div>{{ $goodsReceipt->purchaseOrder->vendor->name }}</div>
                <div>{{ $goodsReceipt->purchaseOrder->vendor->address }}</div>
                <div>{{ $goodsReceipt->purchaseOrder->vendor->city }},
                    {{ $goodsReceipt->purchaseOrder->vendor->province }}
                    {{ $goodsReceipt->purchaseOrder->vendor->postal_code }}</div>
                <div>Phone: {{ $goodsReceipt->purchaseOrder->vendor->phone }}</div>
                <div>Email: {{ $goodsReceipt->purchaseOrder->vendor->email }}</div>
            </div>
        </div>
        <div class="info-column">
            <div class="info-box">
                <h3>RECEIPT INFO</h3>
                <div><strong>Purchase Order:</strong> {{ $goodsReceipt->purchaseOrder->po_number }}</div>
                <div><strong>Receipt Date:</strong> {{ $goodsReceipt->receipt_date->format('M d, Y') }}</div>
                <div><strong>Warehouse:</strong> {{ $goodsReceipt->warehouse->wh_desc }}</div>
                <div><strong>Delivery Note:</strong> {{ $goodsReceipt->delivery_note_number ?: 'Not specified' }}</div>
                <div><strong>Reference:</strong> {{ $goodsReceipt->reference_number ?: 'Not specified' }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Unit Cost</th>
                <th>Lot Number</th>
                <th>Expiry Date</th>
                <th>Location</th>
                <th>Total Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($goodsReceipt->items as $item)
                <tr>
                    <td>{{ $item->inventory->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->unit_cost, 2) }}</td>
                    <td>{{ $item->lot_number ?: '—' }}</td>
                    <td>{{ $item->expiry_date ? $item->expiry_date->format('M d, Y') : '—' }}</td>
                    <td>{{ $item->location_in_warehouse ?: '—' }}</td>
                    <td>${{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($goodsReceipt->notes)
        <div class="notes">
            <h3>Notes:</h3>
            <p>{{ $goodsReceipt->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <p>This document confirms the receipt of goods as detailed above.</p>
        <p>Generated on {{ now()->format('M d, Y h:i A') }}</p>
    </div>
</body>

</html>

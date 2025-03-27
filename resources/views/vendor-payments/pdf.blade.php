<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Payment Receipt #{{ $vendorPayment->payment_number }}</title>
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

        .amount-box {
            text-align: center;
            padding: 15px;
            border: 2px solid #333;
            border-radius: 5px;
            margin: 20px auto;
            width: 60%;
        }

        .amount-box .amount {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
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

        .signature {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
        }

        .signature-line {
            display: inline-block;
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 40px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>PAYMENT RECEIPT</h1>
        <h2>{{ $vendorPayment->payment_number }}</h2>
    </div>

    <div class="info-section">
        <div class="info-column">
            <div class="info-box">
                <h3>PAID TO</h3>
                <div>{{ $vendorPayment->vendor->name }}</div>
                <div>{{ $vendorPayment->vendor->address }}</div>
                <div>{{ $vendorPayment->vendor->city }}, {{ $vendorPayment->vendor->province }}
                    {{ $vendorPayment->vendor->postal_code }}</div>
                <div>Phone: {{ $vendorPayment->vendor->phone }}</div>
                <div>Email: {{ $vendorPayment->vendor->email }}</div>
            </div>
        </div>
        <div class="info-column">
            <div class="info-box">
                <h3>PAYMENT INFO</h3>
                <div><strong>Purchase Order:</strong> {{ $vendorPayment->purchaseOrder->po_number }}</div>
                <div><strong>Payment Date:</strong> {{ $vendorPayment->payment_date->format('M d, Y') }}</div>
                <div><strong>Payment Method:</strong>
                    @switch($vendorPayment->payment_method)
                        @case('cash')
                            Cash
                        @break

                        @case('bank_transfer')
                            Bank Transfer
                        @break

                        @case('check')
                            Check
                        @break

                        @case('credit_card')
                            Credit Card
                        @break

                        @default
                            {{ $vendorPayment->payment_method }}
                    @endswitch
                </div>
                <div><strong>Reference Number:</strong> {{ $vendorPayment->reference_number ?: 'Not specified' }}</div>
            </div>
        </div>
    </div>

    <div class="amount-box">
        <div>PAYMENT AMOUNT</div>
        <div class="amount">${{ number_format($vendorPayment->amount, 2) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order Reference</th>
                <th>Order Date</th>
                <th>Original Amount</th>
                <th>Previously Paid</th>
                <th>This Payment</th>
                <th>Remaining Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $vendorPayment->purchaseOrder->po_number }}</td>
                <td>{{ $vendorPayment->purchaseOrder->order_date->format('M d, Y') }}</td>
                <td>${{ number_format($vendorPayment->purchaseOrder->total_amount, 2) }}</td>
                <td>${{ number_format($vendorPayment->purchaseOrder->payments->where('id', '!=', $vendorPayment->id)->sum('amount'), 2) }}
                </td>
                <td>${{ number_format($vendorPayment->amount, 2) }}</td>
                <td>${{ number_format(
                    max(0, $vendorPayment->purchaseOrder->total_amount - $vendorPayment->purchaseOrder->payments->sum('amount')),
                    2,
                ) }}
                </td>
            </tr>
        </tbody>
    </table>

    @if ($vendorPayment->notes)
        <div class="notes">
            <h3>Notes:</h3>
            <p>{{ $vendorPayment->notes }}</p>
        </div>
    @endif

    <div class="signature">
        <div class="signature-line"></div>
        <div>Authorized Signature</div>
    </div>

    <div class="footer">
        <p>Thank you for your business.</p>
        <p>Generated on {{ now()->format('M d, Y h:i A') }}</p>
    </div>
</body>

</html>

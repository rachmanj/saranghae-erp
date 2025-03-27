<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PurchaseOrder;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class VendorPaymentController extends Controller
{
    /**
     * Display a listing of vendor payments.
     */
    public function index()
    {
        $vendorPayments = VendorPayment::with(['vendor', 'purchaseOrder'])
            ->orderBy('payment_date', 'desc')
            ->paginate(10);
            
        return view('vendor-payments.index', compact('vendorPayments'));
    }

    /**
     * Show the form for creating a new vendor payment.
     */
    public function create(Request $request)
    {
        $purchaseOrderId = $request->query('purchase_order_id');
        $purchaseOrder = null;
        
        if ($purchaseOrderId) {
            $purchaseOrder = PurchaseOrder::with(['vendor'])
                ->findOrFail($purchaseOrderId);
                
            // Check if purchase order is in a state where payment can be made
            if ($purchaseOrder->payment_status === PurchaseOrder::PAYMENT_PAID) {
                return redirect()->route('purchase-orders.show', $purchaseOrder)
                    ->with('error', 'This purchase order is already fully paid.');
            }
        }
        
        $vendors = Partner::where('type', Partner::TYPE_VENDOR)->get();
        $purchaseOrders = PurchaseOrder::with('vendor')
            ->whereIn('payment_status', [
                PurchaseOrder::PAYMENT_UNPAID,
                PurchaseOrder::PAYMENT_PARTIALLY_PAID
            ])
            ->get();
            
        $paymentMethods = [
            VendorPayment::PAYMENT_METHOD_CASH => 'Cash',
            VendorPayment::PAYMENT_METHOD_BANK_TRANSFER => 'Bank Transfer',
            VendorPayment::PAYMENT_METHOD_CHECK => 'Check',
            VendorPayment::PAYMENT_METHOD_CREDIT_CARD => 'Credit Card',
        ];
        
        return view('vendor-payments.create', compact(
            'vendors', 
            'purchaseOrders', 
            'purchaseOrder', 
            'paymentMethods'
        ));
    }

    /**
     * Store a newly created vendor payment in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        // Get the purchase order
        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
        
        // Check if purchase order is in a state where payment can be made
        if ($purchaseOrder->payment_status === PurchaseOrder::PAYMENT_PAID) {
            return back()->withInput()->withErrors(['error' => 'This purchase order is already fully paid.']);
        }
        
        // Check if the payment amount is valid
        $totalPaid = $purchaseOrder->payments()->sum('amount');
        $remainingAmount = $purchaseOrder->total_amount - $totalPaid;
        
        if ($validated['amount'] > $remainingAmount) {
            return back()->withInput()->withErrors(['amount' => 'The payment amount cannot exceed the remaining amount.']);
        }
        
        try {
            DB::beginTransaction();
            
            // Generate unique payment number
            $paymentNumber = 'VP-' . date('Ymd') . '-' . str_pad(VendorPayment::count() + 1, 4, '0', STR_PAD_LEFT);
            
            // Create vendor payment
            $vendorPayment = VendorPayment::create([
                'payment_number' => $paymentNumber,
                'partner_id' => $validated['partner_id'],
                'purchase_order_id' => $validated['purchase_order_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Update purchase order payment status
            $this->updatePurchaseOrderPaymentStatus($purchaseOrder);
            
            DB::commit();
            
            return redirect()->route('vendor-payments.show', $vendorPayment)
                ->with('success', 'Vendor payment created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create vendor payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified vendor payment.
     */
    public function show(VendorPayment $vendorPayment)
    {
        $vendorPayment->load(['vendor', 'purchaseOrder']);
        
        return view('vendor-payments.show', compact('vendorPayment'));
    }

    /**
     * Generate PDF for the vendor payment.
     */
    public function generatePdf(VendorPayment $vendorPayment)
    {
        $vendorPayment->load(['vendor', 'purchaseOrder']);
        
        $pdf = PDF::loadView('vendor-payments.pdf', compact('vendorPayment'));
        
        return $pdf->download('vendor-payment-' . $vendorPayment->payment_number . '.pdf');
    }
    
    /**
     * Update the payment status of a purchase order.
     */
    private function updatePurchaseOrderPaymentStatus(PurchaseOrder $purchaseOrder)
    {
        $totalPaid = $purchaseOrder->payments()->sum('amount');
        
        if ($totalPaid >= $purchaseOrder->total_amount) {
            $purchaseOrder->update(['payment_status' => PurchaseOrder::PAYMENT_PAID]);
        } elseif ($totalPaid > 0) {
            $purchaseOrder->update(['payment_status' => PurchaseOrder::PAYMENT_PARTIALLY_PAID]);
        } else {
            $purchaseOrder->update(['payment_status' => PurchaseOrder::PAYMENT_UNPAID]);
        }
        
        return $purchaseOrder;
    }
} 
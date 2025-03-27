<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the purchase orders.
     */
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('vendor')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create()
    {
        $vendors = Partner::where('type', Partner::TYPE_VENDOR)->get();
        $inventories = Inventory::all();
        
        return view('purchase-orders.create', compact('vendors', 'inventories'));
    }

    /**
     * Store a newly created purchase order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Generate unique PO number (you might want to customize this)
            $poNumber = 'PO-' . date('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
            
            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            $discountAmount = 0;
            
            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTaxAmount = $itemSubtotal * ($item['tax_rate'] ?? 0) / 100;
                $itemDiscountAmount = $itemSubtotal * ($item['discount_percent'] ?? 0) / 100;
                
                $subtotal += $itemSubtotal;
                $taxAmount += $itemTaxAmount;
                $discountAmount += $itemDiscountAmount;
            }
            
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            
            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'partner_id' => $validated['partner_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'payment_status' => PurchaseOrder::PAYMENT_UNPAID,
                'created_by' => auth()->id(),
            ]);
            
            // Create purchase order items
            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTaxAmount = $itemSubtotal * ($item['tax_rate'] ?? 0) / 100;
                $itemDiscountAmount = $itemSubtotal * ($item['discount_percent'] ?? 0) / 100;
                $itemTotal = $itemSubtotal + $itemTaxAmount - $itemDiscountAmount;
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $itemTaxAmount,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'discount_amount' => $itemDiscountAmount,
                    'subtotal' => $itemSubtotal,
                    'total' => $itemTotal,
                    'description' => $item['description'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create purchase order: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.inventory', 'receipts']);
        
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        // Only draft purchase orders can be edited
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft purchase orders can be edited.');
        }
        
        $purchaseOrder->load(['items.inventory']);
        $vendors = Partner::where('type', Partner::TYPE_VENDOR)->get();
        $inventories = Inventory::all();
        
        return view('purchase-orders.edit', compact('purchaseOrder', 'vendors', 'inventories'));
    }

    /**
     * Update the specified purchase order in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Only draft purchase orders can be updated
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft purchase orders can be updated.');
        }
        
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_order_items,id',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            $discountAmount = 0;
            
            // Track existing item IDs to determine which ones to delete
            $existingItemIds = $purchaseOrder->items->pluck('id')->toArray();
            $updatedItemIds = [];
            
            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTaxAmount = $itemSubtotal * ($item['tax_rate'] ?? 0) / 100;
                $itemDiscountAmount = $itemSubtotal * ($item['discount_percent'] ?? 0) / 100;
                
                $subtotal += $itemSubtotal;
                $taxAmount += $itemTaxAmount;
                $discountAmount += $itemDiscountAmount;
                
                if (isset($item['id'])) {
                    $updatedItemIds[] = $item['id'];
                }
            }
            
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            
            // Update purchase order
            $purchaseOrder->update([
                'partner_id' => $validated['partner_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            // Update or create purchase order items
            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTaxAmount = $itemSubtotal * ($item['tax_rate'] ?? 0) / 100;
                $itemDiscountAmount = $itemSubtotal * ($item['discount_percent'] ?? 0) / 100;
                $itemTotal = $itemSubtotal + $itemTaxAmount - $itemDiscountAmount;
                
                $itemData = [
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $itemTaxAmount,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'discount_amount' => $itemDiscountAmount,
                    'subtotal' => $itemSubtotal,
                    'total' => $itemTotal,
                    'description' => $item['description'] ?? null,
                ];
                
                if (isset($item['id'])) {
                    // Update existing item
                    PurchaseOrderItem::where('id', $item['id'])->update($itemData);
                } else {
                    // Create new item
                    PurchaseOrderItem::create($itemData);
                }
            }
            
            // Delete items that were removed
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (!empty($itemsToDelete)) {
                PurchaseOrderItem::whereIn('id', $itemsToDelete)->delete();
            }
            
            DB::commit();
            
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update purchase order: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified purchase order from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Only draft purchase orders can be deleted
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->with('error', 'Only draft purchase orders can be deleted.');
        }
        
        try {
            DB::beginTransaction();
            
            // Delete purchase order items first
            $purchaseOrder->items()->delete();
            
            // Delete purchase order
            $purchaseOrder->delete();
            
            DB::commit();
            
            return redirect()->route('purchase-orders.index')
                ->with('success', 'Purchase order deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete purchase order: ' . $e->getMessage());
        }
    }
    
    /**
     * Change the status of the purchase order to 'sent'.
     */
    public function sendPurchaseOrder(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->with('error', 'Only draft purchase orders can be sent.');
        }
        
        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_SENT]);
        
        return back()->with('success', 'Purchase order has been marked as sent.');
    }
    
    /**
     * Generate PDF for the purchase order.
     */
    public function generatePdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.inventory']);
        
        $pdf = PDF::loadView('purchase-orders.pdf', compact('purchaseOrder'));
        
        return $pdf->download('purchase-order-' . $purchaseOrder->po_number . '.pdf');
    }
} 
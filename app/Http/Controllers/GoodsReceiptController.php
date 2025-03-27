<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Warehouse;
use App\Models\InventoryWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class GoodsReceiptController extends Controller
{
    /**
     * Display a listing of the goods receipts.
     */
    public function index()
    {
        $goodsReceipts = GoodsReceipt::with(['purchaseOrder.vendor', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('goods-receipts.index', compact('goodsReceipts'));
    }

    /**
     * Show the form for creating a new goods receipt.
     */
    public function create(Request $request)
    {
        $purchaseOrderId = $request->query('purchase_order_id');
        $purchaseOrder = null;
        
        if ($purchaseOrderId) {
            $purchaseOrder = PurchaseOrder::with(['vendor', 'items.inventory'])
                ->findOrFail($purchaseOrderId);
                
            // Check if purchase order is in a state where goods can be received
            if (!in_array($purchaseOrder->status, [
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED
            ])) {
                return redirect()->route('purchase-orders.show', $purchaseOrder)
                    ->with('error', 'This purchase order is not in a state where goods can be received.');
            }
        }
        
        $purchaseOrders = PurchaseOrder::with('vendor')
            ->whereIn('status', [
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED
            ])
            ->get();
            
        $warehouses = Warehouse::where('is_active', true)->get();
        
        return view('goods-receipts.create', compact('purchaseOrders', 'purchaseOrder', 'warehouses'));
    }

    /**
     * Store a newly created goods receipt in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'required|date',
            'delivery_note_number' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.lot_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.location_in_warehouse' => 'nullable|string',
        ]);
        
        // Get the purchase order
        $purchaseOrder = PurchaseOrder::findOrFail($validated['purchase_order_id']);
        
        // Check if purchase order is in a state where goods can be received
        if (!in_array($purchaseOrder->status, [
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED
        ])) {
            return back()->withInput()->withErrors(['error' => 'This purchase order is not in a state where goods can be received.']);
        }
        
        try {
            DB::beginTransaction();
            
            // Generate unique receipt number
            $receiptNumber = 'GR-' . date('Ymd') . '-' . str_pad(GoodsReceipt::count() + 1, 4, '0', STR_PAD_LEFT);
            
            // Create goods receipt
            $goodsReceipt = GoodsReceipt::create([
                'receipt_number' => $receiptNumber,
                'purchase_order_id' => $validated['purchase_order_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'receipt_date' => $validated['receipt_date'],
                'delivery_note_number' => $validated['delivery_note_number'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Create goods receipt items and update inventory
            foreach ($validated['items'] as $item) {
                // Create goods receipt item
                $receiptItem = GoodsReceiptItem::create([
                    'goods_receipt_id' => $goodsReceipt->id,
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'lot_number' => $item['lot_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'unit_cost' => $purchaseOrder->items()->where('id', $item['purchase_order_item_id'])->value('unit_price') ?? 0,
                    'location_in_warehouse' => $item['location_in_warehouse'] ?? null,
                ]);
                
                // Update inventory in warehouse
                $inventoryWarehouse = InventoryWarehouse::firstOrNew([
                    'inventory_id' => $item['inventory_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                ]);
                
                $currentQuantity = $inventoryWarehouse->stock_quantity ?? 0;
                $currentValue = $inventoryWarehouse->stock_value ?? 0;
                $newQuantity = $currentQuantity + $item['quantity'];
                $newValue = $currentValue + ($item['quantity'] * $receiptItem->unit_cost);
                
                $inventoryWarehouse->fill([
                    'stock_quantity' => $newQuantity,
                    'stock_value' => $newValue,
                    'location_in_warehouse' => $item['location_in_warehouse'] ?? $inventoryWarehouse->location_in_warehouse,
                    'created_by' => $inventoryWarehouse->exists ? $inventoryWarehouse->created_by : auth()->id(),
                ]);
                
                $inventoryWarehouse->save();
            }
            
            // Update purchase order status
            $this->updatePurchaseOrderStatus($purchaseOrder);
            
            DB::commit();
            
            return redirect()->route('goods-receipts.show', $goodsReceipt)
                ->with('success', 'Goods receipt created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create goods receipt: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified goods receipt.
     */
    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['purchaseOrder.vendor', 'warehouse', 'items.inventory']);
        
        return view('goods-receipts.show', compact('goodsReceipt'));
    }

    /**
     * Generate PDF for the goods receipt.
     */
    public function generatePdf(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['purchaseOrder.vendor', 'warehouse', 'items.inventory']);
        
        $pdf = PDF::loadView('goods-receipts.pdf', compact('goodsReceipt'));
        
        return $pdf->download('goods-receipt-' . $goodsReceipt->receipt_number . '.pdf');
    }
    
    /**
     * Update the status of a purchase order based on received items.
     */
    private function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['items']);
        
        $allItemsReceived = true;
        $anyItemReceived = false;
        
        foreach ($purchaseOrder->items as $item) {
            $receivedQuantity = $item->receivedItems()->sum('quantity');
            
            if ($receivedQuantity > 0) {
                $anyItemReceived = true;
            }
            
            if ($receivedQuantity < $item->quantity) {
                $allItemsReceived = false;
            }
        }
        
        if ($allItemsReceived) {
            $purchaseOrder->update(['status' => PurchaseOrder::STATUS_FULLY_RECEIVED]);
        } elseif ($anyItemReceived) {
            $purchaseOrder->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
        }
        
        return $purchaseOrder;
    }
} 
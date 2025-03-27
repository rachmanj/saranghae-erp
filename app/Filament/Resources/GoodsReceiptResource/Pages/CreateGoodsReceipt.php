<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Filament\Resources\GoodsReceiptResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Create the goods receipt
            $goodsReceipt = static::getModel()::create([
                'receipt_number' => $data['receipt_number'],
                'purchase_order_id' => $data['purchase_order_id'],
                'warehouse_id' => $data['warehouse_id'],
                'receipt_date' => $data['receipt_date'],
                'delivery_note_number' => $data['delivery_note_number'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Create receipt items and update inventory
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                    
                    if (!$poItem) continue;
                    
                    // Create goods receipt item
                    $receiptItem = $goodsReceipt->items()->create([
                        'purchase_order_item_id' => $item['purchase_order_item_id'],
                        'inventory_id' => $item['inventory_id'],
                        'quantity' => $item['quantity'],
                        'lot_number' => $item['lot_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'unit_cost' => $poItem->unit_price,
                        'location_in_warehouse' => $item['location_in_warehouse'] ?? null,
                    ]);
                    
                    // Update inventory in warehouse
                    $inventoryWarehouse = \App\Models\InventoryWarehouse::firstOrNew([
                        'inventory_id' => $item['inventory_id'],
                        'warehouse_id' => $data['warehouse_id'],
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
            }
            
            // Update purchase order status
            $purchaseOrder = PurchaseOrder::find($data['purchase_order_id']);
            if ($purchaseOrder) {
                $this->updatePurchaseOrderStatus($purchaseOrder);
            }
            
            return $goodsReceipt;
        });
    }
    
    private function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
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
    }
} 
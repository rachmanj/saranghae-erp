<?php

namespace App\Filament\Resources\VendorPaymentResource\Pages;

use App\Filament\Resources\VendorPaymentResource;
use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateVendorPayment extends CreateRecord
{
    protected static string $resource = VendorPaymentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Create vendor payment
            $vendorPayment = static::getModel()::create([
                'payment_number' => $data['payment_number'],
                'partner_id' => $data['partner_id'],
                'purchase_order_id' => $data['purchase_order_id'],
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Update purchase order payment status
            $purchaseOrder = PurchaseOrder::find($data['purchase_order_id']);
            if ($purchaseOrder) {
                $this->updatePurchaseOrderPaymentStatus($purchaseOrder);
            }
            
            return $vendorPayment;
        });
    }
    
    private function updatePurchaseOrderPaymentStatus(PurchaseOrder $purchaseOrder): void
    {
        $totalPaid = $purchaseOrder->payments()->sum('amount');
        
        if ($totalPaid >= $purchaseOrder->total_amount) {
            $purchaseOrder->update(['payment_status' => PurchaseOrder::PAYMENT_PAID]);
        } elseif ($totalPaid > 0) {
            $purchaseOrder->update(['payment_status' => PurchaseOrder::PAYMENT_PARTIALLY_PAID]);
        } else {
            $purchaseOrder->update(['payment_status' => PurchaseOrder::PAYMENT_UNPAID]);
        }
    }
} 
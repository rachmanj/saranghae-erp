<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrder::STATUS_DRAFT),
            Actions\Action::make('send')
                ->label('Mark as Sent')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrder::STATUS_DRAFT)
                ->action(function () {
                    $this->record->update(['status' => PurchaseOrder::STATUS_SENT]);
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Actions\Action::make('print')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (PurchaseOrder $record): string => route('purchase-orders.pdf', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('createGoodsReceipt')
                ->label('Receive Goods')
                ->icon('heroicon-o-truck')
                ->visible(fn (PurchaseOrder $record): bool => 
                    in_array($record->status, [
                        PurchaseOrder::STATUS_SENT, 
                        PurchaseOrder::STATUS_PARTIALLY_RECEIVED
                    ])
                )
                ->url(fn (PurchaseOrder $record): string => route('goods-receipts.create', ['purchase_order_id' => $record->id]))
                ->openUrlInNewTab(),
            Actions\Action::make('createVendorPayment')
                ->label('Make Payment')
                ->icon('heroicon-o-banknotes')
                ->visible(fn (PurchaseOrder $record): bool => 
                    in_array($record->payment_status, [
                        PurchaseOrder::PAYMENT_UNPAID, 
                        PurchaseOrder::PAYMENT_PARTIALLY_PAID
                    ])
                )
                ->url(fn (PurchaseOrder $record): string => route('vendor-payments.create', ['purchase_order_id' => $record->id]))
                ->openUrlInNewTab(),
        ];
    }
} 
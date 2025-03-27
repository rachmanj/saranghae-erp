<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Filament\Resources\GoodsReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceipt extends ViewRecord
{
    protected static string $resource = GoodsReceiptResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('goods-receipts.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('viewPurchaseOrder')
                ->label('View Purchase Order')
                ->icon('heroicon-o-shopping-cart')
                ->url(fn () => route('filament.admin.resources.purchase-orders.view', ['record' => $this->record->purchase_order_id]))
                ->openUrlInNewTab(),
        ];
    }
} 
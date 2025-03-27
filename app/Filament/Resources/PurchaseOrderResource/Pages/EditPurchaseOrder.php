<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Repeater;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrder::STATUS_DRAFT),
            Actions\Action::make('send')
                ->label('Mark as Sent')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (PurchaseOrder $record): bool => $record->status === PurchaseOrder::STATUS_DRAFT)
                ->action(function () {
                    $this->record->update(['status' => PurchaseOrder::STATUS_SENT]);
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    
    // Define action listeners for the form
    protected function afterFill(): void
    {
        // Initial calculation when form loads
        $this->recalculateTotals();
    }
    
    // Add listeners for repeater actions
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Schedule recalculation after form is filled
        $this->recalculateTotals();
        
        return $data;
    }
    
    // Handle item changes to recalculate totals
    public function recalculateTotals(): void
    {
        $form = $this->form;
        $component = $form->getComponent('items');
        
        if ($component) {
            PurchaseOrderResource::calculateTotals(
                $component, 
                fn ($key) => data_get($this->data, $key), 
                function(string $path, $value) {
                    data_set($this->data, $path, $value);
                }
            );
        }
    }
    
    // Add listeners for repeater events
    public function updatedFormData(array $data): void
    {
        $this->recalculateTotals();
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Calculate subtotal, tax, discount, and total
        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;
        
        // Check if items exist in the data array
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTaxAmount = $itemSubtotal * ($item['tax_rate'] ?? 0) / 100;
                $itemDiscountAmount = $itemSubtotal * ($item['discount_percent'] ?? 0) / 100;
                $itemTotal = $itemSubtotal + $itemTaxAmount - $itemDiscountAmount;
                
                // Set the item-level calculated values
                $data['items'][$key]['subtotal'] = $itemSubtotal;
                $data['items'][$key]['tax_amount'] = $itemTaxAmount;
                $data['items'][$key]['discount_amount'] = $itemDiscountAmount;
                $data['items'][$key]['total'] = $itemTotal;
                
                $subtotal += $itemSubtotal;
                $taxAmount += $itemTaxAmount;
                $discountAmount += $itemDiscountAmount;
            }
        }
        
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        
        // Set the calculated values
        $data['subtotal'] = $subtotal;
        $data['tax_amount'] = $taxAmount;
        $data['discount_amount'] = $discountAmount;
        $data['total_amount'] = $totalAmount;
        
        $record->update($data);
        
        return $record;
    }
} 
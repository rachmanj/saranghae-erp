<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    
    protected static function booted()
    {
        static::creating(function ($item) {
            // Ensure subtotal and other calculated fields are set
            if (empty($item->subtotal)) {
                $item->subtotal = $item->quantity * $item->unit_price;
            }
            
            if (empty($item->tax_amount)) {
                $item->tax_amount = $item->subtotal * ($item->tax_rate / 100);
            }
            
            if (empty($item->discount_amount)) {
                $item->discount_amount = $item->subtotal * ($item->discount_percent / 100);
            }
            
            if (empty($item->total)) {
                $item->total = $item->subtotal + $item->tax_amount - $item->discount_amount;
            }
        });
        
        static::updating(function ($item) {
            // Update subtotal and other calculated fields
            $item->subtotal = $item->quantity * $item->unit_price;
            $item->tax_amount = $item->subtotal * ($item->tax_rate / 100);
            $item->discount_amount = $item->subtotal * ($item->discount_percent / 100);
            $item->total = $item->subtotal + $item->tax_amount - $item->discount_amount;
        });
    }
    
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
    
    public function receivedItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
    
    // Get quantity received so far
    public function getReceivedQuantityAttribute()
    {
        return $this->receivedItems->sum('quantity');
    }
    
    // Get remaining quantity to be received
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->received_quantity;
    }
} 
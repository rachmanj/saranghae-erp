<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'receipt_date' => 'date',
    ];
    
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
    
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
    
    // Payment status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    const STATUS_FULLY_RECEIVED = 'fully_received';
    const STATUS_CANCELLED = 'cancelled';
    
    // Payment status constants
    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PARTIALLY_PAID = 'partially_paid';
    const PAYMENT_PAID = 'paid';
    
    protected static function booted()
    {
        static::creating(function ($purchaseOrder) {
            if (empty($purchaseOrder->po_number)) {
                $purchaseOrder->po_number = 'PO-' . date('Ymd') . '-' . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
    
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    
    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }
    
    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }
    
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 
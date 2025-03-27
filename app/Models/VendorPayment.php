<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayment extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];
    
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_CHECK = 'check';
    const PAYMENT_METHOD_CREDIT_CARD = 'credit_card';
    
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
    
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 
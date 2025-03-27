<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = [];
    
    // Partner types
    const TYPE_VENDOR = 'vendor';
    const TYPE_CUSTOMER = 'customer';
    
    public function inventories()
    {
        return $this->belongsToMany(Inventory::class)
            ->withPivot('price', 'quantity')
            ->withTimestamps();
    }
    
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'partner_id');
    }
    
    public function payments()
    {
        return $this->hasMany(VendorPayment::class, 'partner_id');
    }
    
    // Check if partner is a vendor
    public function isVendor()
    {
        return $this->type === self::TYPE_VENDOR;
    }
    
    // Check if partner is a customer
    public function isCustomer()
    {
        return $this->type === self::TYPE_CUSTOMER;
    }
}

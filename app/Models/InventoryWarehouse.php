<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryWarehouse extends Model
{
    protected $table = 'inventory_warehouse';
    
    protected $fillable = [
        'inventory_id',
        'warehouse',
        'stock_quantity',
        'stock_value',
        'location_in_warehouse',
        'created_by'
    ];
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}

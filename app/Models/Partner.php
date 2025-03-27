<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = [];
    
    public function inventories()
    {
        return $this->belongsToMany(Inventory::class)
            ->withPivot('price', 'quantity')
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $guarded = [];

    public function partners()
    {
        return $this->belongsToMany(Partner::class)
            ->withPivot('price', 'quantity')
            ->withTimestamps();
    }
}

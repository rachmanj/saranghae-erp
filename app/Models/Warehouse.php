<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'wh_code',
        'wh_desc',
        'wh_location',
        'is_active',
        'created_by'
    ];
}

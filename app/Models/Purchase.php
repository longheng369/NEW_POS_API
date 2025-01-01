<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'purchases';
    protected $fillable = [
        'supplier_id',
        'user_id',
        'tax_rate',
        'discount',
        'status',
        'grand_total',
        'notes',
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'product_id',
        'variant_id',
        'unit_id',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
        'price_per_piece',
        'expiration_date',
        'batch_number',
    ];
    
}

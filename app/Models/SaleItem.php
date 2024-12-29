<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'variant_id',
        'unit_id',
        'quantity',
        'unit_price',
        'price_per_piece',
        'discount',
        'subtotal',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}

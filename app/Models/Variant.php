<?php

namespace App\Models;

use App\Models\Unit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Variant extends Model
{
    use HasFactory;

    protected $table = 'variants';

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'alert_quantity',
        'costing',
        'price',
        'previous_price',
        'unit_id',
        'stock',
        'conversion_factor',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $table = 'sales';

    protected $fillable = [
        'reference_no',
        'customer_id',
        'user_id',
        'tax_rate',
        'discount',
        'status',
        'grand_total',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}

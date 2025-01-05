<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\User;
use App\Models\PurchaseItem;
use App\Models\Payment;

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
        'date',
        'reference_number'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

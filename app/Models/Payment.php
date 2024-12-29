<?php

namespace App\Models;

use App\Models\Sale;
use App\Models\User;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    // Define the table name if it's different from the default
    protected $table = 'payments';

    // Specify which columns can be mass-assigned
    protected $fillable = [
        'user_id',
        'sale_id',
        'purchase_id',
        'amount',
        'payment_method',
        'payment_date',
        'status',
    ];

    // Define relationships

    /**
     * A payment belongs to a sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /** 
     * A paymet belongs to a purchase. 
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * A payment is handled by a user (cashier or salesperson).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    

    /**
     * Calculate the total payment amount after applying tax and discount.
     */
    public function calculateTotalAmount()
    {
        $total = $this->amount;
        if ($this->tax) {
            $total += $this->tax;
        }
        if ($this->discount) {
            $total -= $this->discount;
        }
        return $total;
    }
}

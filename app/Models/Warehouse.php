<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    protected $fillable = [
        'name', 'location', 'capacity', 
        'manager_id', 'contact_number', 'status', 'notes'
    ];
    

    public function manager() : BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}

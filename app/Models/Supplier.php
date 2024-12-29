<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $fillable = ['name', 'company_name', 'contact_person', 'phone_number', 'email', 'address', 'website', 'notes', 'status'];

    public function products() : HasMany 
    {
        return $this->hasMany(Product::class);
    }
}

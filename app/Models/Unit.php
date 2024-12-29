<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = ['name', 'code', 'base_unit_id', 'conversion_factor'];

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }
    
    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

}

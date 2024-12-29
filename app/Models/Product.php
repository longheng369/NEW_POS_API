<?php

namespace App\Models;

use App\Models\Unit;
use App\Models\Brand;
use App\Models\Variant;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'type', 
        'code', 
        'name', 
        'status', 
        'image', 
        'barcode_symbology', 
        'category_id', 
        'supplier_id', 
        'brand_id', 
        'warehouse_id', 
        'base_unit_id', 
        'unit_id', 
        'conversion_factor',
        'promotion',
        'discount',
        'start_date',
        'end_date',
        'tax_rate', 
        'details', 
        'is_perishable',
    ];

    protected $attributes = [
        'type' => 'standard',
        'status' => true,
        'barcode_symbology' => 'code128',
        'promotion' => false,
    ];

    // Relationships

    // A product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // A product belongs to a supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // A product belongs to a brand
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // A product belongs to a warehouse
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // A product belongs to a unit (main unit)
    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

   
}

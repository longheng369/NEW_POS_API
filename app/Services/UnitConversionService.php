<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitConversion;

class UnitConversionService
{
    /**
     * Convert a target unit quantity to the base unit quantity.
     *
     * @param int $productId
     * @param int $targetUnitId
     * @param float $quantity
     * @return float
     * @throws \Exception
     */
    function convertToBaseUnit(int $productId, int $unitId, float $quantity) {
        // Get the product's base unit
        $product = Product::findOrFail($productId);
        $baseUnitId = $product->base_unit_id;
    
        // Check if the target unit is the base unit
        if ($baseUnitId == $unitId) {
            return $quantity; // No conversion needed
        }

        if($product->unit_id){
            return $quantity * $product->conversion_factor;
        }
    
        $unitConversionFactor = Unit::where('id', $unitId)->where('base_unit_id', $product->base_unit_id)->firstOrFail();
    
        // Convert quantity to base unit
        return $quantity * $unitConversionFactor->conversion_factor;
        // return $quantity;
    }
}

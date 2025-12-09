<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'original_price',
        'currency',
        'image_url',
        'supplier_id',
        'category_id',
        'supplier_product_id',
        'supplier_url',
        'is_active',
        'stock_status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Format price with currency
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2, ',', '.') . ' ' . ($this->currency ?? 'KM');
    }
}
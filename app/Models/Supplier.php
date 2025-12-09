<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'import_method',
        'url',
        'xml_file_path',
        'css_selector',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get products from this supplier
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Check if supplier uses web scraping
     */
    public function isScrapingMethod()
    {
        return $this->import_method === 'scraping';
    }

    /**
     * Check if supplier uses XML upload
     */
    public function isXmlMethod()
    {
        return $this->import_method === 'xml';
    }
}
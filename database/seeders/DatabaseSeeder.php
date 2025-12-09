<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Categories
        $categories = [
            ['name' => 'Laptopi i računari', 'slug' => 'laptopi-racunari', 'icon' => '💻'],
            ['name' => 'Telefoni', 'slug' => 'telefoni', 'icon' => '📱'],
            ['name' => 'Bijela tehnika', 'slug' => 'bijela-tehnika', 'icon' => '🏠'],
            ['name' => 'Periferija', 'slug' => 'periferija', 'icon' => '🎧'],
            ['name' => 'Komponente', 'slug' => 'komponente', 'icon' => '💾'],
            ['name' => 'Kablovi i punjači', 'slug' => 'kablovi-punjaci', 'icon' => '🔌'],
            ['name' => 'Slušalice', 'slug' => 'slusalice', 'icon' => '🎧'],
            ['name' => 'Mala kućanska tehnika', 'slug' => 'mala-kucanska-tehnika', 'icon' => '🔌'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create Suppliers
        $suppliers = [
            [
                'name' => 'Gigatron',
                'import_method' => 'scraping',
                'url' => 'https://gigatron.rs',
                'css_selector' => '.product-item',
                'is_active' => true,
                'last_synced_at' => now()->subHours(2),
            ],
            [
                'name' => 'Emmi',
                'import_method' => 'scraping',
                'url' => 'https://emmi.rs',
                'css_selector' => '.product-card',
                'is_active' => true,
                'last_synced_at' => now()->subHours(3),
            ],
            [
                'name' => 'WinWin',
                'import_method' => 'scraping',
                'url' => 'https://winwin.ba',
                'css_selector' => '.item',
                'is_active' => false,
                'last_synced_at' => now()->subDays(5),
            ],
            [
                'name' => 'Tech Distributor',
                'import_method' => 'xml',
                'xml_file_path' => 'suppliers/xml/tech-distributor.xml',
                'is_active' => true,
                'last_synced_at' => now()->subHour(),
            ],
        ];

        foreach ($suppliers as $supplierData) {
            $supplier = Supplier::create($supplierData);

            // Create sample products for each supplier
            $this->createProductsForSupplier($supplier);
        }
    }

    /**
     * Create sample products for a supplier
     */
    private function createProductsForSupplier(Supplier $supplier)
    {
        $products = [
            [
                'name' => 'Lenovo ThinkPad X1 Carbon Gen 11',
                'description' => 'Profesionalni poslovni laptop sa Intel Core i7 procesorom, 16GB RAM-a i 512GB SSD-a.',
                'price' => 2499.00,
                'category_id' => 1, // Laptopi
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Flagship telefon sa 200MP kamerom, Snapdragon 8 Gen 3 i S Pen-om.',
                'price' => 1899.00,
                'category_id' => 2, // Telefoni
            ],
            [
                'name' => 'Gorenje WaveActive Mašina za pranje veša',
                'description' => 'Energetski efikasna mašina za pranje veša, kapacitet 8kg, A+++',
                'price' => 799.00,
                'category_id' => 3, // Bijela tehnika
            ],
            [
                'name' => 'Logitech MX Master 3S',
                'description' => 'Bežični ergonomski miš za produktivnost sa silent klikovima.',
                'price' => 189.00,
                'category_id' => 4, // Periferija
            ],
            [
                'name' => 'Samsung 980 PRO 1TB NVMe SSD',
                'description' => 'Brz NVMe SSD sa brzinama do 7000MB/s, idealan za gaming i profesionalnu upotrebu.',
                'price' => 299.00,
                'category_id' => 5, // Komponente
            ],
        ];

        foreach ($products as $productData) {
            Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'description' => $productData['description'],
                'price' => $productData['price'],
                'original_price' => $productData['price'] * 1.2,
                'currency' => 'KM',
                'supplier_id' => $supplier->id,
                'category_id' => $productData['category_id'],
                'supplier_product_id' => Str::random(10),
                'is_active' => true,
                'stock_status' => 'in_stock',
            ]);
        }
    }
}
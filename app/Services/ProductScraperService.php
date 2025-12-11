<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ProductScraperService
{
    /**
     * Scrape products from a supplier website
     */
    public function scrapeSupplier(Supplier $supplier)
    {
        if (!$supplier->is_active) {
            return [
                'success' => false,
                'message' => 'Dobavljač nije aktivan.',
            ];
        }

        if ($supplier->import_method === 'scraping') {
            return $this->scrapeFromWebsite($supplier);
        } elseif ($supplier->import_method === 'xml') {
            return $this->parseXmlFile($supplier);
        }

        return [
            'success' => false,
            'message' => 'Nepoznata metoda uvoza.',
        ];
    }

    /**
     * Scrape products from website using CSS selectors
     */
    protected function scrapeFromWebsite(Supplier $supplier)
    {
        try {
            // Fetch the website
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($supplier->url);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Nije moguće pristupiti sajtu.',
                ];
            }

            // Parse HTML
            $html = $response->body();
            $crawler = new Crawler($html);

            // Check if the page uses JavaScript frameworks
            $usesJavaScript = $this->detectJavaScriptFramework($html);
            if ($usesJavaScript) {
                \Log::warning("Website {$supplier->url} uses JavaScript framework: {$usesJavaScript}. Products may not be visible in HTML.");
            }

            // Auto-detect or use provided selector
            $selector = $supplier->css_selector;

            if (!$selector) {
                $selector = $this->autoDetectSelector($crawler);

                if (!$selector) {
                    // Save HTML for debugging
                    $this->saveDebugHtml($supplier, $html);

                    return [
                        'success' => false,
                        'message' => 'Nije moguće automatski pronaći proizvode na stranici.' .
                                   ($usesJavaScript ? " Stranica koristi {$usesJavaScript} - proizvodi se možda učitavaju preko JavaScript-a." : ''),
                    ];
                }
            }

            $productElements = $crawler->filter($selector);

            if ($productElements->count() === 0) {
                // Save HTML for debugging
                $this->saveDebugHtml($supplier, $html);

                return [
                    'success' => false,
                    'message' => 'Nije pronađen nijedan proizvod sa selektorom: ' . $selector .
                               ($usesJavaScript ? " (Stranica koristi {$usesJavaScript})" : ''),
                ];
            }

            $addedCount = 0;
            $updatedCount = 0;

            // Process each product
            $productElements->each(function (Crawler $node) use ($supplier, &$addedCount, &$updatedCount) {
                try {
                    $productData = $this->extractProductData($node, $supplier);
                    
                    if ($productData) {
                        $result = $this->saveProduct($productData, $supplier);
                        if ($result === 'added') {
                            $addedCount++;
                        } elseif ($result === 'updated') {
                            $updatedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    // Skip failed products
                    \Log::error('Product extraction failed: ' . $e->getMessage());
                }
            });

            // Update last sync time
            $supplier->last_synced_at = now();
            $supplier->save();

            return [
                'success' => true,
                'message' => "Dodano: {$addedCount}, Ažurirano: {$updatedCount}",
                'added' => $addedCount,
                'updated' => $updatedCount,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Greška: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Auto-detect product selector from common patterns
     */
    protected function autoDetectSelector(Crawler $crawler)
    {
        // Expanded list of common product selectors
        $selectors = [
            // Common product classes
            '.product',
            '.product-item',
            '.product-card',
            '.product-box',
            '.product-grid-item',
            '.product-list-item',
            '.product-wrapper',
            '.item-product',
            '.grid-item',
            '.catalog-item',
            '.shop-item',

            // E-commerce platform specific
            '.woocommerce-LoopProduct-link',
            '.product-small',
            '.type-product',
            '.product-container',

            // Data attributes
            '[data-product]',
            '[data-product-id]',
            '[data-testid*="product"]',

            // Semantic HTML
            'article.product',
            'div.product',
            'li.product',

            // More generic but common
            '.item',
            'article',
            '.card',
            '.box',

            // Shopify
            '.product-card-wrapper',
            '.grid__item',

            // Magento
            '.product-item-info',
            '.product-item-details',
        ];

        foreach ($selectors as $selector) {
            try {
                $count = $crawler->filter($selector)->count();
                // Lowered threshold to 2 products for better detection
                if ($count >= 2) {
                    \Log::info("Auto-detected selector: {$selector} ({$count} products)");
                    return $selector;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Detect if page uses JavaScript frameworks
     */
    protected function detectJavaScriptFramework($html)
    {
        // Check for React
        if (preg_match('/__NEXT_DATA__|nextjs|_next\/static|react/i', $html)) {
            return 'Next.js/React';
        }

        // Check for Vue
        if (preg_match('/vue\.js|__NUXT__|nuxt/i', $html)) {
            return 'Vue.js/Nuxt';
        }

        // Check for Angular
        if (preg_match('/ng-app|angular/i', $html)) {
            return 'Angular';
        }

        // Check for common SPA indicators
        if (preg_match('/<div[^>]+id=["\']root["\']|<div[^>]+id=["\']app["\']/', $html) &&
            strlen($html) < 10000) { // Small HTML usually means SPA
            return 'JavaScript SPA';
        }

        return null;
    }

    /**
     * Save HTML for debugging purposes
     */
    protected function saveDebugHtml($supplier, $html)
    {
        try {
            $debugPath = storage_path('logs/scraper-debug');

            if (!file_exists($debugPath)) {
                mkdir($debugPath, 0755, true);
            }

            $filename = 'debug-' . Str::slug($supplier->name) . '-' . date('Y-m-d-H-i-s') . '.html';
            $filepath = $debugPath . '/' . $filename;

            file_put_contents($filepath, $html);

            \Log::info("Saved debug HTML for {$supplier->name} to {$filepath}");
        } catch (\Exception $e) {
            \Log::error("Failed to save debug HTML: " . $e->getMessage());
        }
    }

    /**
     * Extract product data from HTML node
     */
    protected function extractProductData(Crawler $node, Supplier $supplier)
    {
        // Try different selectors for common product attributes
        $name = $this->extractText($node, [
            '.product-title',
            '.product-name',
            'h2',
            'h3',
            '.title',
            '[itemprop="name"]',
        ]);

        $price = $this->extractPrice($node, [
            '.product-price',
            '.price',
            '[itemprop="price"]',
            '.amount',
        ]);

        $image = $this->extractImage($node, [
            '.product-image img',
            '.image img',
            'img',
        ]);

        $link = $this->extractLink($node, [
            'a',
            '.product-link',
        ]);

        // Must have at least name and price
        if (!$name || !$price) {
            return null;
        }

        return [
            'name' => $name,
            'price' => $price,
            'image_url' => $image,
            'supplier_url' => $link,
        ];
    }

    /**
     * Extract text from node using multiple selectors
     */
    protected function extractText(Crawler $node, array $selectors)
    {
        foreach ($selectors as $selector) {
            try {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    return trim($element->text());
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }

    /**
     * Extract price from node
     */
    protected function extractPrice(Crawler $node, array $selectors)
    {
        $priceText = $this->extractText($node, $selectors);
        
        if (!$priceText) {
            return null;
        }

        // Remove currency symbols and convert to decimal
        $price = preg_replace('/[^\d,.]/', '', $priceText);
        $price = str_replace(',', '.', $price);
        
        return (float) $price;
    }

    /**
     * Extract image URL from node
     */
    protected function extractImage(Crawler $node, array $selectors)
    {
        foreach ($selectors as $selector) {
            try {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $src = $element->attr('src') ?: $element->attr('data-src');
                    
                    if ($src) {
                        // Make absolute URL if relative
                        if (!str_starts_with($src, 'http')) {
                            $src = rtrim(parse_url($node->getUri(), PHP_URL_SCHEME) . '://' . parse_url($node->getUri(), PHP_URL_HOST), '/') . '/' . ltrim($src, '/');
                        }
                        return $src;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }

    /**
     * Extract product link from node
     */
    protected function extractLink(Crawler $node, array $selectors)
    {
        foreach ($selectors as $selector) {
            try {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $href = $element->attr('href');
                    
                    if ($href) {
                        // Make absolute URL if relative
                        if (!str_starts_with($href, 'http')) {
                            $baseUrl = parse_url($node->getUri(), PHP_URL_SCHEME) . '://' . parse_url($node->getUri(), PHP_URL_HOST);
                            $href = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
                        }
                        return $href;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }

    /**
     * Parse XML file for products
     */
    protected function parseXmlFile(Supplier $supplier)
    {
        try {
            $xmlPath = storage_path('app/public/' . $supplier->xml_file_path);

            if (!file_exists($xmlPath)) {
                return [
                    'success' => false,
                    'message' => 'XML fajl ne postoji.',
                ];
            }

            $xml = simplexml_load_file($xmlPath);
            
            if (!$xml) {
                return [
                    'success' => false,
                    'message' => 'Nije moguće parsirati XML fajl.',
                ];
            }

            $addedCount = 0;
            $updatedCount = 0;

            // Parse products from XML
            foreach ($xml->product as $productXml) {
                $productData = [
                    'name' => (string) $productXml->name,
                    'price' => (float) $productXml->price,
                    'image_url' => (string) $productXml->image,
                    'description' => (string) $productXml->description,
                    'supplier_url' => (string) $productXml->url,
                ];

                $result = $this->saveProduct($productData, $supplier);
                if ($result === 'added') {
                    $addedCount++;
                } elseif ($result === 'updated') {
                    $updatedCount++;
                }
            }

            // Update last sync time
            $supplier->last_synced_at = now();
            $supplier->save();

            return [
                'success' => true,
                'message' => "Dodano: {$addedCount}, Ažurirano: {$updatedCount}",
                'added' => $addedCount,
                'updated' => $updatedCount,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Greška: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Save or update product
     */
    protected function saveProduct(array $productData, Supplier $supplier)
    {
        $slug = Str::slug($productData['name']);

        // Check if product already exists
        $product = Product::where('supplier_id', $supplier->id)
            ->where('slug', $slug)
            ->first();

        // Try to match category
        $category = $this->matchCategory($productData['name']);

        if ($product) {
            // Update existing product
            $product->update([
                'name' => $productData['name'],
                'price' => $productData['price'],
                'image_url' => $productData['image_url'] ?? $product->image_url,
                'description' => $productData['description'] ?? $product->description,
                'supplier_url' => $productData['supplier_url'] ?? $product->supplier_url,
                'category_id' => $category?->id ?? $product->category_id,
                'is_active' => true,
            ]);

            return 'updated';
        } else {
            // Create new product
            Product::create([
                'name' => $productData['name'],
                'slug' => $slug,
                'price' => $productData['price'],
                'original_price' => $productData['price'] * 1.2, // 20% više
                'image_url' => $productData['image_url'] ?? null,
                'description' => $productData['description'] ?? null,
                'supplier_url' => $productData['supplier_url'] ?? null,
                'supplier_id' => $supplier->id,
                'category_id' => $category?->id,
                'is_active' => true,
                'currency' => 'KM',
            ]);

            return 'added';
        }
    }

    /**
     * Try to match product to category based on name
     */
    protected function matchCategory(string $productName)
    {
        $productNameLower = mb_strtolower($productName);

        $categoryKeywords = [
            'laptopi-racunari' => ['laptop', 'notebook', 'računar', 'racunar', 'desktop', 'pc'],
            'telefoni' => ['telefon', 'smartphone', 'iphone', 'samsung', 'xiaomi', 'mobile'],
            'bijela-tehnika' => ['mašina', 'masina', 'frižider', 'frizider', 'zamrzivač', 'klima', 'bojler', 'šporet', 'sporet', 'rerna'],
            'periferija' => ['miš', 'mis', 'tastatura', 'monitor', 'zvučnici', 'zvucnici', 'slušalice', 'slusalice'],
            'komponente' => ['ssd', 'ram', 'memorija', 'grafička', 'graficka', 'procesor', 'matična', 'maticna'],
            'kablovi-punjaci' => ['kabl', 'punjač', 'punjac', 'adapter', 'usb', 'hdmi'],
        ];

        foreach ($categoryKeywords as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($productNameLower, $keyword)) {
                    return Category::where('slug', $slug)->first();
                }
            }
        }

        return null;
    }
}
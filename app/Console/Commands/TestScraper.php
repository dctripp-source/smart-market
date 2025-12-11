<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class TestScraper extends Command
{
    protected $signature = 'scraper:test {url} {selector?}';
    protected $description = 'Testiraj web scraping na URL-u sa CSS selektorom';

    public function handle()
    {
        $url = $this->argument('url');
        $selector = $this->argument('selector');

        $this->info("Testiram scraping...");
        $this->info("URL: {$url}");
        
        if ($selector) {
            $this->info("Selector: {$selector}\n");
        } else {
            $this->info("Selector: AUTO-DETECT\n");
        }

        try {
            // Fetch website
            $this->info("Preuzimam stranicu...");
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'sr-RS,sr;q=0.9,en;q=0.8',
                ])
                ->get($url);

            if (!$response->successful()) {
                $this->error("Greška: HTTP status {$response->status()}");
                return 1;
            }

            $this->info("✓ Stranica preuzeta ({$response->status()})\n");

            // Parse HTML
            $crawler = new Crawler($response->body());

            // If no selector provided, try to auto-detect
            if (!$selector) {
                $this->info("🔍 Tražim proizvode sa popularnim selektorima...\n");
                $selector = $this->autoDetectSelector($crawler);
                
                if (!$selector) {
                    $this->error("✗ Nije moguće automatski pronaći proizvode.");
                    $this->newLine();
                    $this->info("💡 Pokušaj ručno sa:");
                    $this->info("   php artisan scraper:test \"{$url}\" \".your-selector\"");
                    return 1;
                }
                
                $this->info("✓ Pronađen selector: {$selector}\n");
            }
            
            // Find elements
            $elements = $crawler->filter($selector);
            $count = $elements->count();

            if ($count === 0) {
                $this->error("✗ Nije pronađen nijedan element sa selektorom: {$selector}");
                $this->newLine();
                $this->info("Pokušaj sa drugim selektorima:");
                $this->suggestSelectors($crawler);
                return 1;
            }

            $this->info("✓ Pronađeno {$count} elemenata\n");

            // Show first 3 products
            $this->info("Primjeri proizvoda:\n");
            
            $elements->slice(0, 3)->each(function (Crawler $node, $i) {
                $this->line("═══ Proizvod " . ($i + 1) . " ═══");
                
                // Try to extract product data
                $name = $this->tryExtract($node, [
                    '.product-title', '.product-name', '.title', 'h2', 'h3', 'h4', 
                    '.name', '[itemprop="name"]', 'a[title]'
                ]);
                $this->info("Naziv: " . ($name ?: '❌ Nije pronađen'));

                $price = $this->tryExtract($node, [
                    '.product-price', '.price', '[itemprop="price"]', '.amount',
                    '.product-price-new', '.sale-price', '.current-price'
                ]);
                $this->info("Cijena: " . ($price ?: '❌ Nije pronađena'));

                $image = $this->tryExtractImage($node);
                $this->info("Slika: " . ($image ? '✓ Pronađena' : '❌ Nije pronađena'));

                $link = $this->tryExtractLink($node);
                $this->info("Link: " . ($link ? Str::limit($link, 50) : '❌ Nije pronađen'));

                $this->newLine();
            });

            $this->info("✓ Test uspješan!");
            $this->newLine();
            $this->info("💡 Koristi ovaj selector u CMS-u:");
            $this->line("   CSS Selector: {$selector}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Greška: " . $e->getMessage());
            return 1;
        }
    }

    protected function autoDetectSelector(Crawler $crawler)
    {
        $selectors = [
            '.product',
            '.product-item',
            '.product-card',
            '.product-box',
            '.item-product',
            '.item',
            '.grid-item',
            '.product-grid-item',
            '[data-product]',
            '[data-product-id]',
            'article.product',
            'div.product',
            '.catalog-item',
            '.shop-item',
        ];

        foreach ($selectors as $selector) {
            try {
                $count = $crawler->filter($selector)->count();
                if ($count >= 3) { // At least 3 products
                    return $selector;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    protected function tryExtract(Crawler $node, array $selectors)
    {
        foreach ($selectors as $selector) {
            try {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $text = trim($element->text());
                    if (!empty($text)) {
                        return $text;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Try to get title attribute from link
        try {
            $link = $node->filter('a[title]');
            if ($link->count() > 0) {
                return $link->attr('title');
            }
        } catch (\Exception $e) {
            // Continue
        }
        
        return null;
    }

    protected function tryExtractImage(Crawler $node)
    {
        $selectors = ['.product-image img', '.image img', 'img', 'picture img'];
        
        foreach ($selectors as $selector) {
            try {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $src = $element->attr('src') ?: $element->attr('data-src') ?: $element->attr('data-lazy');
                    if ($src && !empty($src)) {
                        return $src;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }

    protected function tryExtractLink(Crawler $node)
    {
        try {
            $element = $node->filter('a');
            if ($element->count() > 0) {
                return $element->attr('href');
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    protected function suggestSelectors(Crawler $crawler)
    {
        $suggestions = [
            '.product',
            '.product-item',
            '.product-card',
            '.product-box',
            '.item-product',
            '.item',
            '[data-product]',
            '.grid-item',
            'article',
        ];

        foreach ($suggestions as $selector) {
            try {
                $count = $crawler->filter($selector)->count();
                if ($count > 0) {
                    $this->line("  • {$selector} → {$count} elemenata");
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
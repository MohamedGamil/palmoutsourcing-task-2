<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Product Seeder
 * 
 * Seeds database with sample products for development and testing
 * Creates balanced data across platforms (Amazon and Jumia)
 * Implements requirements: REQ-TEST-001, REQ-TEST-002
 */
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates:
     * - 10 Amazon products (5 active, 5 inactive)
     * - 10 Jumia products (5 active, 5 inactive)
     * - Mix of recently scraped and never scraped products
     */
    public function run(): void
    {
        // Create Amazon products
        $this->createAmazonProducts();
        
        // Create Jumia products
        $this->createJumiaProducts();
        
        $this->command->info('Created 20 products: 10 Amazon, 10 Jumia');
    }

    /**
     * Create Amazon product samples
     */
    private function createAmazonProducts(): void
    {
        // 5 active Amazon products (recently scraped)
        Product::factory()
            ->amazon()
            ->active()
            ->recentlyScraped()
            ->count(5)
            ->create();

        // 3 active Amazon products (need update)
        Product::factory()
            ->amazon()
            ->active()
            ->needsUpdate()
            ->count(3)
            ->create();

        // 2 inactive Amazon products
        Product::factory()
            ->amazon()
            ->inactive()
            ->count(2)
            ->create();
    }

    /**
     * Create Jumia product samples
     */
    private function createJumiaProducts(): void
    {
        // 5 active Jumia products (recently scraped)
        Product::factory()
            ->jumia()
            ->active()
            ->recentlyScraped()
            ->count(5)
            ->create();

        // 3 active Jumia products (never scraped)
        Product::factory()
            ->jumia()
            ->active()
            ->neverScraped()
            ->count(3)
            ->create();

        // 2 inactive Jumia products
        Product::factory()
            ->jumia()
            ->inactive()
            ->count(2)
            ->create();
    }
}

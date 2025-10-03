<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Product Factory
 * 
 * Generates test data for product watching system
 * Implements requirements: REQ-TEST-001, REQ-TEST-002
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Amazon product URL templates
     */
    private const AMAZON_URL_TEMPLATES = [
        'https://www.amazon.com/dp/%s',
        'https://www.amazon.com/gp/product/%s',
        'https://www.amazon.co.uk/dp/%s',
    ];

    /**
     * Jumia product URL templates
     */
    private const JUMIA_URL_TEMPLATES = [
        'https://www.jumia.com.ng/product-%s.html',
        'https://www.jumia.co.ke/product-%s.html',
        'https://www.jumia.com.eg/product-%s.html',
    ];

    /**
     * Sample product titles by category
     */
    private const PRODUCT_TITLES = [
        'Sony WH-1000XM4 Wireless Headphones',
        'Apple iPhone 15 Pro Max 256GB',
        'Samsung Galaxy S24 Ultra Smartphone',
        'Dell XPS 13 Laptop Computer',
        'LG OLED 55" 4K Smart TV',
        'Bose QuietComfort Earbuds II',
        'Canon EOS R6 Mirrorless Camera',
        'KitchenAid Stand Mixer 5-Quart',
        'Dyson V15 Detect Cordless Vacuum',
        'Nintendo Switch OLED Gaming Console',
        'Fitbit Charge 6 Fitness Tracker',
        'Amazon Echo Dot 5th Gen Smart Speaker',
        'Microsoft Surface Pro 9 Tablet',
        'GoPro HERO12 Black Action Camera',
        'Instant Pot Duo Plus 6 Quart',
    ];

    /**
     * Define the model's default state.
     * Generates realistic product data for testing
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platform = fake()->randomElement(Product::SUPPORTED_PLATFORMS);
        $currency = $this->getCurrencyForPlatform($platform);

        return [
            'title' => fake()->randomElement(self::PRODUCT_TITLES),
            'price' => fake()->randomFloat(2, 19.99, 1999.99),
            'price_currency' => $currency,
            'rating' => fake()->randomFloat(2, 1, 5),
            'rating_count' => fake()->numberBetween(0, 10000),
            'platform_category' => fake()->randomElement($this->getPlatformCategories($platform)),
            'image_url' => fake()->imageUrl(640, 480, 'products', true),
            'product_url' => $this->generateProductUrl($platform),
            'platform' => $platform,
            'last_scraped_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'scrape_count' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(85), // 85% active products
        ];
    }

    /**
     * State for Amazon products
     *
     * @return static
     */
    public function amazon(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => Product::PLATFORM_AMAZON,
            'product_url' => $this->generateProductUrl(Product::PLATFORM_AMAZON),
        ]);
    }

    /**
     * State for Jumia products
     *
     * @return static
     */
    public function jumia(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => Product::PLATFORM_JUMIA,
            'product_url' => $this->generateProductUrl(Product::PLATFORM_JUMIA),
        ]);
    }

    /**
     * State for active products
     *
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * State for inactive products
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * State for recently scraped products
     *
     * @return static
     */
    public function recentlyScraped(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_scraped_at' => fake()->dateTimeBetween('-24 hours', 'now'),
            'scrape_count' => fake()->numberBetween(5, 50),
        ]);
    }

    /**
     * State for never scraped products
     *
     * @return static
     */
    public function neverScraped(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_scraped_at' => null,
            'scrape_count' => 0,
        ]);
    }

    /**
     * State for products needing update
     *
     * @return static
     */
    public function needsUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_scraped_at' => fake()->dateTimeBetween('-30 days', '-2 days'),
            'is_active' => true,
        ]);
    }

    /**
     * Generate a realistic product URL for the given platform
     *
     * @param string $platform
     * @return string
     */
    private function generateProductUrl(string $platform): string
    {
        if ($platform === Product::PLATFORM_AMAZON) {
            $template = fake()->randomElement(self::AMAZON_URL_TEMPLATES);
            $productId = strtoupper(fake()->bothify('??########'));
            return sprintf($template, $productId);
        }
        
        // Jumia
        $template = fake()->randomElement(self::JUMIA_URL_TEMPLATES);
        $slug = fake()->slug(3);
        return sprintf($template, $slug);
    }

    /**
     * Get platform-specific categories
     *
     * @param string $platform
     * @return array
     */
    private function getPlatformCategories(string $platform): array
    {
        if ($platform === Product::PLATFORM_AMAZON) {
            return [
                'Electronics',
                'Books',
                'Home & Kitchen',
                'Clothing & Accessories',
                'Sports & Outdoors',
                'Health & Personal Care',
                'Toys & Games',
                'Automotive',
            ];
        }

        // Jumia categories
        return [
            'Electronics',
            'Fashion',
            'Home & Living',
            'Health & Beauty',
            'Baby Products',
            'Groceries',
            'Sports & Fitness',
            'Automotive',
        ];
    }

    /**
     * Get currency for platform
     *
     * @param string $platform
     * @return string
     */
    private function getCurrencyForPlatform(string $platform): string
    {
        if ($platform === Product::PLATFORM_AMAZON) {
            // Amazon supports multiple currencies
            return fake()->randomElement(['USD', 'GBP', 'EUR', 'CAD']);
        }

        // Jumia primarily uses local currencies in Africa
        return fake()->randomElement(['NGN', 'KES', 'EGP', 'USD']);
    }
}

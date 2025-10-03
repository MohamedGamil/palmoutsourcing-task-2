<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Product Model
 * 
 * Represents a product being watched from e-commerce platforms (Amazon, Jumia)
 * Implements requirements: REQ-MODEL-001 through REQ-MODEL-009
 * 
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model for watching e-commerce products",
 *     required={"title", "price", "product_url", "platform"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Product ID",
 *         readOnly=true,
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Product title",
 *         maxLength=500,
 *         example="Sony WH-1000XM4 Wireless Headphones"
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="number",
 *         format="float",
 *         description="Product price",
 *         example=349.99
 *     ),
 *     @OA\Property(
 *         property="rating",
 *         type="number",
 *         format="float",
 *         description="Product rating (0-5)",
 *         nullable=true,
 *         minimum=0,
 *         maximum=5,
 *         example=4.5
 *     ),
 *     @OA\Property(
 *         property="rating_count",
 *         type="integer",
 *         description="Number of ratings",
 *         nullable=true,
 *         minimum=0,
 *         example=1250
 *     ),
 *     @OA\Property(
 *         property="image_url",
 *         type="string",
 *         description="Product image URL",
 *         nullable=true,
 *         maxLength=2048,
 *         example="https://m.media-amazon.com/images/I/71o8Q5XJS5L._AC_SX679_.jpg"
 *     ),
 *     @OA\Property(
 *         property="product_url",
 *         type="string",
 *         description="URL of the product being watched",
 *         maxLength=2048,
 *         example="https://www.amazon.com/dp/B0863TXGM3"
 *     ),
 *     @OA\Property(
 *         property="platform",
 *         type="string",
 *         description="Source platform (amazon or jumia)",
 *         enum={"amazon", "jumia"},
 *         example="amazon"
 *     ),
 *     @OA\Property(
 *         property="platform_category",
 *         type="string",
 *         description="Category from the platform",
 *         nullable=true,
 *         maxLength=255,
 *         example="Electronics"
 *     ),
 *     @OA\Property(
 *         property="last_scraped_at",
 *         type="string",
 *         format="date-time",
 *         description="Last successful scrape timestamp",
 *         nullable=true,
 *         example="2025-10-03T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="scrape_count",
 *         type="integer",
 *         description="Number of times product has been scraped",
 *         default=0,
 *         example=15
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Whether product is actively being watched",
 *         default=true,
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         readOnly=true,
 *         example="2025-10-01T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         readOnly=true,
 *         example="2025-10-03T12:00:00Z"
 *     )
 * )
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    /**
     * Supported e-commerce platforms
     * As per REQ-WATCH-001
     */
    public const PLATFORM_AMAZON = 'amazon';
    public const PLATFORM_JUMIA = 'jumia';
    public const SUPPORTED_PLATFORMS = [self::PLATFORM_AMAZON, self::PLATFORM_JUMIA];

    /**
     * The attributes that are mass assignable.
     * As per REQ-MODEL-003
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'price',
        'rating',
        'rating_count',
        'image_url',
        'product_url',
        'platform',
        'platform_category',
        'last_scraped_at',
        'scrape_count',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     * As per REQ-MODEL-004, REQ-MODEL-005, REQ-MODEL-006, REQ-MODEL-007
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'last_scraped_at' => 'datetime',
            'scrape_count' => 'integer',
            'is_active' => 'boolean',
            'rating' => 'decimal:2',
            'rating_count' => 'integer',
        ];
    }

    /**
     * Scope to filter active products
     * As per REQ-MODEL-008
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive products
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter by platform
     * As per REQ-MODEL-009
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $platform
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter Amazon products
     * As per REQ-MODEL-009
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromAmazon($query)
    {
        return $query->where('platform', self::PLATFORM_AMAZON);
    }

    /**
     * Scope to filter Jumia products
     * As per REQ-MODEL-009
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromJumia($query)
    {
        return $query->where('platform', self::PLATFORM_JUMIA);
    }

    /**
     * Scope to filter products scraped recently
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScrapedRecently($query, int $hours = 24)
    {
        return $query->where('last_scraped_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to filter products needing update
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsUpdate($query, int $hours = 24)
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_scraped_at')
              ->orWhere('last_scraped_at', '<', now()->subHours($hours));
        })->where('is_active', true);
    }

    /**
     * Check if product is from Amazon
     *
     * @return bool
     */
    public function isAmazon(): bool
    {
        return $this->platform === self::PLATFORM_AMAZON;
    }

    /**
     * Check if product is from Jumia
     *
     * @return bool
     */
    public function isJumia(): bool
    {
        return $this->platform === self::PLATFORM_JUMIA;
    }

    /**
     * Mark product as scraped
     *
     * @return void
     */
    public function markAsScraped(): void
    {
        $this->increment('scrape_count');
        $this->update(['last_scraped_at' => now()]);
    }

    /**
     * Activate product watching
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate product watching
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Boot the model
     * Implements validation as per REQ-VAL-002, REQ-VAL-006
     */
    protected static function booted(): void
    {
        static::creating(function ($product) {
            self::validatePlatform($product);
            self::validateProductUrl($product);
            self::validateRating($product);
        });

        static::updating(function ($product) {
            self::validatePlatform($product);
            
            // Only validate URL if it has changed
            if ($product->isDirty('product_url')) {
                self::validateProductUrl($product);
            }

            self::validateRating($product);
        });
    }

    /**
     * Validate platform value
     *
     * @param Product $product
     * @throws \InvalidArgumentException
     */
    private static function validatePlatform(Product $product): void
    {
        if (!in_array($product->platform, self::SUPPORTED_PLATFORMS)) {
            throw new \InvalidArgumentException(
                'Platform must be either "' . self::PLATFORM_AMAZON . '" or "' . self::PLATFORM_JUMIA . '"'
            );
        }
    }

    /**
     * Validate product URL matches the platform
     *
     * @param Product $product
     * @throws \InvalidArgumentException
     */
    private static function validateProductUrl(Product $product): void
    {
        if (empty($product->product_url)) {
            return;
        }

        $url = strtolower($product->product_url);
        
        if ($product->platform === self::PLATFORM_AMAZON) {
            if (!str_contains($url, 'amazon.com') && !str_contains($url, 'amazon.')) {
                throw new \InvalidArgumentException(
                    'Product URL must be a valid Amazon URL for amazon platform'
                );
            }
        } elseif ($product->platform === self::PLATFORM_JUMIA) {
            if (!str_contains($url, 'jumia.')) {
                throw new \InvalidArgumentException(
                    'Product URL must be a valid Jumia URL for jumia platform'
                );
            }
        }
    }

    private static function validateRating(Product $product): void
    {
        if (!is_null($product->rating)) {
            if ($product->rating < 0 || $product->rating > 5) {
                throw new \InvalidArgumentException('Rating must be between 0 and 5');
            }
        }

        if (!is_null($product->rating_count)) {
            if ($product->rating_count < 0) {
                throw new \InvalidArgumentException('Rating count cannot be negative');
            }
        }
    }
}

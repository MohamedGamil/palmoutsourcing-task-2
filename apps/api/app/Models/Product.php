<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model",
 *     required={"title", "price", "source_store"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Product ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Product title",
 *         example="Wireless Headphones"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Product description",
 *         example="High-quality wireless headphones with noise cancellation"
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="number",
 *         format="float",
 *         description="Product price",
 *         example=99.99
 *     ),
 *     @OA\Property(
 *         property="list_price",
 *         type="number",
 *         format="float",
 *         description="Product list price",
 *         example=129.99
 *     ),
 *     @OA\Property(
 *         property="rating",
 *         type="number",
 *         format="float",
 *         description="Product rating (1-5)",
 *         example=4.5,
 *         minimum=1,
 *         maximum=5
 *     ),
 *     @OA\Property(
 *         property="rating_count",
 *         type="integer",
 *         description="Number of ratings",
 *         example=150
 *     ),
 *     @OA\Property(
 *         property="vendor_name",
 *         type="string",
 *         description="Vendor name",
 *         example="TechStore Inc."
 *     ),
 *     @OA\Property(
 *         property="image_url",
 *         type="string",
 *         description="Product image URL",
 *         example="https://example.com/images/product.jpg"
 *     ),
 *     @OA\Property(
 *         property="source_store",
 *         type="string",
 *         description="Source store",
 *         enum={"amazon", "jumia"},
 *         example="amazon"
 *     ),
 *     @OA\Property(
 *         property="store_category",
 *         type="string",
 *         description="Store category",
 *         example="Electronics"
 *     ),
 *     @OA\Property(
 *         property="store_url",
 *         type="string",
 *         description="Product URL on source store",
 *         example="https://www.amazon.com/product/123"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         example="2024-01-01T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2024-01-01T12:00:00Z"
 *     )
 * )
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    // Supported source stores
    const SUPPORTED_STORES = ['amazon', 'jumia'];

    protected $fillable = [
        'title',
        'description',
        'price',
        'list_price',
        'rating',
        'rating_count',
        'vendor_name',
        'image_url',
        'source_store',
        'store_category',
        'store_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'list_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    public function scopeFromStore($query, string $store)
    {
        return $query->where('source_store', $store);
    }

    public function scopeFromAmazon($query)
    {
        return $query->where('source_store', 'amazon');
    }

    public function scopeFromJumia($query)
    {
        return $query->where('source_store', 'jumia');
    }

    protected static function booted()
    {
        static::creating(function ($product) {
            if (!in_array($product->source_store, self::SUPPORTED_STORES)) {
                throw new \InvalidArgumentException('source_store must be either "amazon" or "jumia"');
            }

            if ($product->rating !== null && ($product->rating < 1 || $product->rating > 5)) {
                throw new \InvalidArgumentException('rating must be a float value between 1 and 5');
            }
        });

        static::updating(function ($product) {
            if (!in_array($product->source_store, self::SUPPORTED_STORES)) {
                throw new \InvalidArgumentException('source_store must be either "amazon" or "jumia"');
            }

            if ($product->rating !== null && ($product->rating < 1 || $product->rating > 5)) {
                throw new \InvalidArgumentException('rating must be a float value between 1 and 5');
            }
        });
    }
}

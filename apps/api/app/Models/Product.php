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
 *     required={"title", "price"},
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
 *         description="Product rating",
 *         example=4.5
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

    protected $fillable = [
        'title',
        'price',
        'list_price',
        'rating',
        'rating_count',
        'vendor_name',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'list_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
    ];
}

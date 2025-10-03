<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

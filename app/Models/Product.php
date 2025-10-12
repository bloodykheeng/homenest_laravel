<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'rating',
        'discount',
        'status',
        'product_subcategory_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating' => 'decimal:2',
            'discount' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    /**
     * Relationship: Product belongs to a subcategory
     */
    public function subcategory()
    {
        return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id');
    }

    /**
     * Relationship: Product belongs to a category through subcategory
     */
    public function category()
    {
        return $this->hasOneThrough(
            ProductCategory::class,       // Final model
            ProductSubcategory::class,    // Intermediate model
            'id',                         // Foreign key on ProductSubcategory (its PK)
            'id',                         // Foreign key on ProductCategory (its PK)
            'product_subcategory_id',     // Local key on Product (FK → ProductSubcategory)
            'product_category_id'         // Local key on ProductSubcategory (FK → ProductCategory)
        );
    }

    /**
     * Relationship: Product has many photos
     */
    public function photos()
    {
        return $this->hasMany(ProductPhoto::class, 'product_id');
    }

    /**
     * Relationship: Get featured photo for this product
     */
    public function featuredPhoto()
    {
        return $this->hasOne(ProductPhoto::class, 'product_id')->where('featured', true);
    }

    /**
     * Relationship: User who created this product
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: User who last updated this product
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

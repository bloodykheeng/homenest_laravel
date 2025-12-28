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
            ProductCategory::class,
            ProductSubcategory::class,
            'id',
            'id',
            'product_subcategory_id',
            'product_category_id'
        );
    }

    /**
     * Relationship: Product has many attachments
     */
    public function productAttachments()
    {
        return $this->hasMany(ProductAttachment::class, 'product_id');
    }

    /**
     * Relationship: Get featured attachment for this product
     */
    public function featuredAttachment()
    {
        return $this->hasOne(ProductAttachment::class, 'product_id')->where('featured', true);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSubcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'photo_url',
        'product_category_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the photo URL attribute with full path
     */
    public function getPhotoUrlAttribute($value): ?string
    {
        return $value ? asset('storage/'.$value) : null;
    }

    /**
     * Relationship: Subcategory belongs to a category
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Relationship: Subcategory has many products
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'product_subcategory_id');
    }

    /**
     * Relationship: User who created this subcategory
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: User who last updated this subcategory
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

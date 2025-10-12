<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'photo_url',
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
     * Relationship: Category has many subcategories
     */
    public function subcategories()
    {
        return $this->hasMany(ProductSubcategory::class, 'product_category_id');
    }

    /**
     * Relationship: User who created this category
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: User who last updated this category
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

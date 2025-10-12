<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'photo_url',
        'featured',
        'product_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
        ];
    }

    /**
     * Get the photo URL attribute with full path
     */
    public function getPhotoUrlAttribute($value): ?string
    {
        return $value ? asset('storage/'.$value) : null;
    }

    /**
     * Relationship: Photo belongs to a product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relationship: User who created this photo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: User who last updated this photo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

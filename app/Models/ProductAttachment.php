<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'file_path',
        'caption',
        'featured',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'featured' => 'boolean',
    ];

    /**
     * Get the file path attribute with full URL
     */
    public function getFilePathAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

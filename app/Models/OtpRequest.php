<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'code',
        'channel',
        'user_id',
    ];

    /**
     * Relationship: OTP belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

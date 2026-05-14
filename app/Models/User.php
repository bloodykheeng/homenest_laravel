<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'phone',
        'gender',
        'citizenship',
        'city',
        'address',
        'postal_code',
        'allow_notifications',
        'status',
        'lastlogin',
        'photo_url',
        'mobile_app_firebase_token',
        'admin_dashboard_firebase_token',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'lastlogin' => 'datetime',
            'password' => 'hashed',
            'allow_notifications' => 'boolean',
        ];
    }

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['role'];

    /**
     * Get the user's role dynamically.
     *
     * @return string
     */
    public function getRoleAttribute()
    {
        // Get the last (most recent) role of the user
        return $this->roles->last()?->name ?? 'No Role';
    }

    /**
     * Get the photo URL attribute with full path
     */
    public function getPhotoUrlAttribute($value)
    {
        return $value ? asset('storage/'.$value) : null;
    }

    // ========================================
    // Relationships - Self-referential
    // ========================================

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function socialAuthProviders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SocialAuthProvider::class, 'user_id');
    }
}

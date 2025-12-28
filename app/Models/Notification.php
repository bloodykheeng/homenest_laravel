<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'link',
        'start_date',
        'end_date',
        'status',
        'type',
        'gender',
        'target_audience',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    // Users
    public function notificationUsers()
    {
        return $this->hasMany(NotificationUser::class);
    }

    public function Users()
    {
        return $this->belongsToMany(User::class, 'notification_users', 'notification_id', 'user_id')->withTimestamps();
    }

    // Viewed By
    public function viewedBies()
    {
        return $this->hasMany(NotificationViewedBy::class);
    }

    // Created & Updated By
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

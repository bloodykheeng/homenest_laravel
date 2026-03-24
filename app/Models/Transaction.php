<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'order_id',
        'transaction_type',
        'payment_method',
        'status',
        'amount',
        'currency',
        'notes',
        'olycash_purchase_id',
        'olycash_event',
        'olycash_message',
        'olycash_message_details',
        'olycash_payment_type',
        'olycash_buyer_id',
        'olycash_buyer_name',
        'olycash_buyer_telephone',
        'olycash_buyer_email',
        'olycash_code',
        'olycash_quantity',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'olycash_quantity' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
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

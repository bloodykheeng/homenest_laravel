<?php

namespace App\Helpers;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderHelper
{
    /**
     * Generate a unique order number with a date factor.
     * Format: ORD-YYYYMMDD-XXXXXXXX
     * Checks the DB for collisions and retries up to 10 times.
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $candidate = 'ORD-' . $date . '-' . strtoupper(Str::random(8));

            if (! Order::where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback: append microseconds to virtually guarantee uniqueness
        return 'ORD-' . $date . '-' . strtoupper(Str::random(8)) . '-' . now()->format('His');
    }
}

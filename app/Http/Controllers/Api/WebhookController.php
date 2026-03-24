<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Traits\OlyCashTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    use OlyCashTrait;

    /**
     * Handle OlyCash payment webhook callbacks.
     *
     * OlyCash sends two POST requests (application/x-www-form-urlencoded):
     *
     * 1. On initiation:
     *    event, message=initiated, purchase_id, payment_type,
     *    message_details, buyer_id, buyer_name, buyer_telephone, buyer_email_address
     *
     * 2. On completion (success or fail):
     *    event, message=success|fail, purchase_id, code (on success),
     *    payment_type, message_details, quantity, amount, currency
     */
    public function handleOlyCash(Request $request)
    {
        $data = $request->all();

        Log::info('OlyCash webhook received', $data);

        $purchaseId = $data['purchase_id'] ?? null;
        $message = $data['message'] ?? null;
        $event = $data['event'] ?? null;

        if (! $purchaseId || ! $message) {
            Log::warning('OlyCash webhook: missing purchase_id or message', $data);

            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $transaction = Transaction::where('olycash_purchase_id', $purchaseId)->first();

        if (! $transaction) {
            Log::warning("OlyCash webhook: no transaction found for purchase_id={$purchaseId}");

            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $updates = [
            'olycash_event' => $event,
            'olycash_message' => $message,
            'olycash_message_details' => $data['message_details'] ?? null,
            'olycash_payment_type' => $data['payment_type'] ?? null,
            'status' => $this->mapOlyCashMessageToStatus($message),
        ];

        // Fields present on the "initiated" callback
        if (isset($data['buyer_id'])) {
            $updates['olycash_buyer_id'] = $data['buyer_id'];
        }

        if (isset($data['buyer_name'])) {
            $updates['olycash_buyer_name'] = $data['buyer_name'];
        }

        if (isset($data['buyer_telephone'])) {
            $updates['olycash_buyer_telephone'] = $data['buyer_telephone'];
        }

        if (isset($data['buyer_email_address'])) {
            $updates['olycash_buyer_email'] = $data['buyer_email_address'];
        }

        // Fields present on the "success" callback
        if (isset($data['code'])) {
            $updates['olycash_code'] = $data['code'];
        }

        if (isset($data['quantity'])) {
            $updates['olycash_quantity'] = (int) $data['quantity'];
        }

        if (isset($data['amount'])) {
            $updates['amount'] = (float) $data['amount'];
        }

        if (isset($data['currency'])) {
            $updates['currency'] = $data['currency'];
        }

        $transaction->update($updates);

        // Sync the parent order status when payment is confirmed
        if ($message === 'success') {
            $order = Order::find($transaction->order_id);

            if ($order) {
                $order->update(['status' => 'processing']);
            }
        }

        Log::info("OlyCash webhook processed: purchase_id={$purchaseId}, message={$message}");

        return response()->json(['message' => 'OK']);
    }
}

<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait OlyCashTrait
{
    /**
     * Fetch a fresh API key from OlyCash.
     * Returns the key string on success, or null on failure.
     */
    protected function getOlyCashApiKey(): ?string
    {
        $response = Http::post(config('olycash.api_url').'/authorize', [
            'olycash_id' => config('olycash.olycash_id'),
            'password' => config('olycash.password'),
        ]);

        if ($response->successful() && $response->json('message') === 'OK') {
            return $response->json('key');
        }

        Log::error('OlyCash authorize failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Initiate a purchase payment via OlyCash.
     *
     * Required fields in $payload:
     *   item_name, quantity, total, price, currency,
     *   buyer_first_name (or buyer_olycash_id), buyer_last_name,
     *   buyer_email, buyer_telephone, method, post_response_url
     *
     * Optional: fee_paid_by, frequency, sale_split
     *
     * Returns the decoded JSON response array or null on failure.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function initiateOlyCashPayment(array $payload): ?array
    {
        $apiKey = $this->getOlyCashApiKey();

        if (! $apiKey) {
            return null;
        }

        $payload['account_id'] = config('olycash.olycash_id');

        $response = Http::withHeaders([
            'Authorization' => $apiKey,
            'Account' => config('olycash.olycash_id'),
            'Content-Type' => 'application/json',
        ])->post(config('olycash.api_url').'/purchases/pay', $payload);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('OlyCash payment initiation failed', [
            'status' => $response->status(),
            'body' => $response->body(),
            'payload' => $payload,
        ]);

        return null;
    }

    /**
     * Build the webhook URL for OlyCash to post payment callbacks to.
     */
    protected function olyCashWebhookUrl(): string
    {
        return url('/api/webhooks/olycash');
    }

    /**
     * Map an OlyCash webhook message value to a transaction status.
     */
    protected function mapOlyCashMessageToStatus(string $message): string
    {
        return match ($message) {
            'initiated' => 'initiated',
            'success' => 'success',
            'fail' => 'failed',
            default => 'pending',
        };
    }
}

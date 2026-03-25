<?php

namespace App\Http\Controllers\Api;

use App\Helpers\OrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Traits\LoggableTrait;
use App\Traits\OlyCashTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use LoggableTrait, OlyCashTrait;

    private function orderRelations(): array
    {
        return [
            'user',
            'items.product',
            'transactions',
            'createdBy',
            'updatedBy',
        ];
    }

    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with($this->orderRelations());

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->query('search') . '%')
                    ->orWhere('guest_name', 'like', '%' . $request->query('search') . '%')
                    ->orWhere('guest_email', 'like', '%' . $request->query('search') . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('payment_option')) {
            $query->where('payment_option', $request->query('payment_option'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('startDate')) {
            $query->whereDate('created_at', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('created_at', '<=', $request->query('endDate'));
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Orders retrieved successfully',
                'data' => $data,
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show($id)
    {
        $order = Order::with($this->orderRelations())->find($id);

        if (! $order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => 'not_found',
            ], 404);
        }

        return response()->json([
            'message' => 'Order retrieved successfully',
            'data' => $order,
        ]);
    }

    /**
     * Store a new order.
     * If payment_option is "Pay Now", initiates an OlyCash payment and returns purchase details.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_option' => 'required|string|in:Pay Now,Pay on Delivery',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'sometimes|numeric|min:0',
            'shipping_fee' => 'sometimes|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'shipping_address' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
            'guest_name' => 'sometimes|nullable|string|max:255',
            'guest_email' => 'sometimes|nullable|email|max:255',
            'guest_phone' => 'sometimes|nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.product_sku' => 'sometimes|nullable|string|max:100',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'sometimes|nullable|numeric|min:0',
            'items.*.total_price' => 'sometimes|nullable|numeric|min:0',
            // Pay Now specific fields
            'payment_method' => 'sometimes|nullable|string|max:100',
            'buyer_telephone' => 'sometimes|nullable|string|max:50',
            'buyer_first_name' => 'sometimes|nullable|string|max:100',
            'buyer_last_name' => 'sometimes|nullable|string|max:100',
            'buyer_email' => 'sometimes|nullable|email|max:255',
        ]);

        $user = Auth::user();

        // Eager-load products to resolve unit prices without N+1
        $productIds = array_column($validated['items'], 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_number' => OrderHelper::generateOrderNumber(),
                'user_id' => $user?->id,
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'payment_option' => $validated['payment_option'],
                'status' => 'pending',
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'] ?? 0,
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'total' => $validated['total'],
                'shipping_address' => $validated['shipping_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                $unitPrice = $item['unit_price'] ?? $product->price;
                $totalPrice = $item['total_price'] ?? ($unitPrice * $item['quantity']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'] ?? $product->sku ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }

            $olyCashResponse = null;

            if ($validated['payment_option'] === 'Pay Now') {
                $buyerName = $validated['buyer_first_name']
                    ?? $user?->name
                    ?? $validated['guest_name']
                    ?? 'Customer';

                $nameParts = explode(' ', $buyerName, 2);

                $olyCashResponse = $this->initiateOlyCashPayment([
                    'item_name' => 'Order ' . $order->order_number,
                    'quantity' => '1',
                    'total' => (string) $validated['total'],
                    'price' => (string) $validated['total'],
                    'currency' => 'UGX',
                    'buyer_first_name' => $nameParts[0],
                    'buyer_last_name' => $nameParts[1] ?? '',
                    'buyer_email' => $validated['buyer_email'] ?? $user?->email ?? $validated['guest_email'] ?? '',
                    'buyer_telephone' => $validated['buyer_telephone'] ?? $validated['guest_phone'] ?? '',
                    'method' => 'mobile_money',
                    'post_response_url' => $this->olyCashWebhookUrl(),
                    'fee_paid_by' => 'payer',
                ]);

                $transaction = Transaction::create([
                    'order_id' => $order->id,
                    'transaction_type' => 'automatic',
                    'payment_method' => $validated['payment_method'] ?? 'Olycash',
                    'status' => 'initiated',
                    'amount' => $validated['total'],
                    'currency' => 'UGX',
                    'olycash_purchase_id' => $olyCashResponse['purchase_id'] ?? null,
                    'olycash_message' => $olyCashResponse['status'] ?? null,
                    'created_by' => $user?->id,
                    'updated_by' => $user?->id,
                ]);
            }

            DB::commit();

            $order->load($this->orderRelations());

            $this->logActivity('Order Created', "Order '{$order->order_number}' created.", [
                'order_id' => $order->id,
                'payment_option' => $order->payment_option,
            ]);

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $order,
                'olycash' => $olyCashResponse,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Update the specified order (status / notes / shipping address).
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,processing,completed,cancelled',
            'shipping_address' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
        ]);

        $validated['updated_by'] = Auth::id();

        DB::beginTransaction();
        try {
            // Lock the row to prevent concurrent update conflicts
            $order = Order::where('id', $id)->lockForUpdate()->first();

            if (! $order) {
                DB::rollBack();

                return response()->json([
                    'message' => 'Order not found',
                    'error' => 'not_found',
                ], 404);
            }

            $order->update($validated);
            DB::commit();

            $order->load($this->orderRelations());

            $this->logActivity('Order Updated', "Order '{$order->order_number}' updated.", [
                'order_id' => $order->id,
            ]);

            return response()->json([
                'message' => 'Order updated successfully',
                'data' => $order,
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified order.
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => 'not_found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $orderNumber = $order->order_number;
            $order->delete();
            DB::commit();

            $this->logActivity('Order Deleted', "Order '{$orderNumber}' deleted.", [
                'order_id' => $id,
            ]);

            return response()->json([
                'message' => 'Order deleted successfully',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete orders.
     *
     * @param  array<int, array{id: int}>  $itemsToDelete
     */
    public function bulkDestroy(Request $request)
    {
        $items = $request->input('itemsToDelete');

        if (! is_array($items) || empty($items)) {
            return response()->json([
                'message' => 'Invalid or empty data',
                'error' => 'bad_request',
            ], 400);
        }

        $ids = array_column($items, 'id');

        DB::beginTransaction();
        try {
            $orders = Order::whereIn('id', $ids)->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'No matching orders found',
                    'error' => 'not_found',
                ], 404);
            }

            Order::whereIn('id', $ids)->delete();
            DB::commit();

            $this->logActivity('Orders Bulk Deleted', 'Bulk deleted orders.', ['ids' => $ids]);

            return response()->json(['message' => 'Bulk delete successful']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

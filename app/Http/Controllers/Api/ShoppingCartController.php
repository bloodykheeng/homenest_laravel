<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCart;
use App\Traits\LoggableTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShoppingCartController extends Controller
{
    use LoggableTrait;

    /**
     * Eager load relationships for product in cart.
     */
    private function productRelations(): array
    {
        return [
            'product' => function ($query) {
                $query->with([
                    'subcategory',
                    'category',
                    'productAttachments',
                    'featuredAttachment',
                    'colors',
                    'sizes',
                    'additionalInfo',
                    'reviews',
                    'createdBy',
                    'updatedBy',
                ]);
            },
            'createdBy',
            'updatedBy',
        ];
    }

    /**
     * Format cart item to flatten product data with cart fields.
     */
    private function formatCartItem(ShoppingCart $cartItem): array
    {
        $productDetails = $cartItem->product ? $cartItem->product->toArray() : [];

        return array_merge($productDetails, [
            'cart_id' => $cartItem->id,
            'product_id' => $cartItem->product_id,
            'selected_quantity' => $cartItem->selected_quantity,
            'cart_price' => $cartItem->price,
        ]);
    }

    /**
     * Display a listing of cart items.
     */
    public function index(Request $request)
    {
        $query = ShoppingCart::with($this->productRelations());

        // Filter by user_id (returns flattened product data)
        if ($request->has('user_id')) {
            $cartItems = $query->where('created_by', $request->user_id)->get();

            $results = $cartItems->map(function ($cartItem) {
                return $this->formatCartItem($cartItem);
            });

            return response()->json(['data' => $results]);
        }

        // Admin: return all cart items
        $cartItems = $query->latest()->get();
        return response()->json(['data' => $cartItems]);
    }

    /**
     * Display the specified cart item.
     */
    public function show($id)
    {
        $cartItem = ShoppingCart::with($this->productRelations())->find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        return response()->json(['data' => $cartItem]);
    }

    /**
     * Store a newly created cart item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'selected_quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        DB::beginTransaction();
        try {
            // Check if product already exists in user's cart
            $existingCart = ShoppingCart::where('product_id', $validated['product_id'])
                ->where('created_by', $user->id)
                ->first();

            if ($existingCart) {
                $existingCart->update([
                    'selected_quantity' => $existingCart->selected_quantity + $validated['selected_quantity'],
                    'price' => $validated['price'],
                    'updated_by' => $user->id,
                ]);

                $existingCart->load($this->productRelations());

                DB::commit();

                $this->logActivity('Cart Updated', 'Product quantity updated in cart', [
                    'cart_id' => $existingCart->id,
                    'product_id' => $existingCart->product_id,
                    'user' => $user->name,
                ]);

                return response()->json([
                    'message' => 'Cart updated successfully',
                    'data' => $this->formatCartItem($existingCart),
                ]);
            }

            $cartItem = ShoppingCart::create($validated);
            $cartItem->load($this->productRelations());

            DB::commit();

            $this->logActivity('Cart Item Added', 'A new product was added to cart', [
                'cart_id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'user' => $user->name,
            ]);

            return response()->json([
                'message' => 'Item added to cart successfully',
                'data' => $this->formatCartItem($cartItem),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            $this->logActivity('Cart Add Failed', 'Failed to add item to cart: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Error adding item to cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified cart item.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $cartItem = ShoppingCart::where('product_id', $id)
            ->where('created_by', $user->id)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $validated = $request->validate([
            'product_id' => 'sometimes|required|exists:products,id',
            'selected_quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $validated['updated_by'] = $user->id;

        DB::beginTransaction();
        try {
            $cartItem->update($validated);
            $cartItem->load($this->productRelations());

            DB::commit();

            $this->logActivity('Cart Item Updated', 'Cart item was successfully updated', [
                'cart_id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'user' => $user->name,
            ]);

            return response()->json([
                'message' => 'Cart item updated successfully',
                'data' => $this->formatCartItem($cartItem),
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            $this->logActivity('Cart Update Failed', 'Failed to update cart item: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'cart_id' => $cartItem->id,
            ]);

            return response()->json([
                'message' => 'Error updating cart item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified cart item.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $cartItem = ShoppingCart::where('product_id', $id)
            ->where('created_by', $user->id)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        try {
            $productId = $cartItem->product_id;
            $cartItem->delete();

            $this->logActivity('Cart Item Removed', 'Product was removed from cart', [
                'product_id' => $productId,
                'user' => $user->name,
            ]);

            return response()->json(['message' => 'Cart item removed successfully'], 200);
        } catch (Exception $e) {
            $this->logActivity('Cart Remove Failed', 'Failed to remove cart item: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'product_id' => $id,
            ]);

            return response()->json([
                'message' => 'Error removing cart item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync local cart items with server cart.
     */
    public function syncCart(Request $request)
    {
        $validated = $request->validate([
            'carts' => 'array',
            'carts.*.product_id' => 'required|exists:products,id',
            'carts.*.selected_quantity' => 'required|integer|min:1',
            'carts.*.price' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // If carts provided, upsert them
            if (!empty($validated['carts'])) {
                $results = [];

                foreach ($validated['carts'] as $data) {
                    $data['created_by'] = $user->id;
                    $data['updated_by'] = $user->id;

                    $cartItem = ShoppingCart::updateOrCreate(
                        [
                            'product_id' => $data['product_id'],
                            'created_by' => $user->id,
                        ],
                        $data
                    );

                    $cartItem->load($this->productRelations());
                    $results[] = $this->formatCartItem($cartItem);
                }

                DB::commit();

                $this->logActivity('Cart Synced', 'Cart items synchronized successfully', [
                    'items_count' => count($results),
                    'user' => $user->name,
                ]);

                return response()->json([
                    'message' => 'Cart synchronized successfully',
                    'data' => $results,
                ], 201);
            }

            // If no carts provided, return existing cart for user
            $cartItems = ShoppingCart::where('created_by', $user->id)
                ->with($this->productRelations())
                ->get();

            $results = $cartItems->map(function ($cartItem) {
                return $this->formatCartItem($cartItem);
            });

            DB::commit();

            return response()->json([
                'message' => 'Cart retrieved successfully',
                'data' => $results,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            $this->logActivity('Cart Sync Failed', 'Failed to sync cart: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Error synchronizing cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all cart items for the authenticated user.
     */
    public function clearCart()
    {
        $user = Auth::user();

        try {
            $deletedCount = ShoppingCart::where('created_by', $user->id)->delete();

            $this->logActivity('Cart Cleared', 'All cart items were cleared', [
                'items_cleared' => $deletedCount,
                'user' => $user->name,
            ]);

            return response()->json([
                'message' => 'Cart cleared successfully',
                'items_cleared' => $deletedCount,
            ]);
        } catch (Exception $e) {
            $this->logActivity('Cart Clear Failed', 'Failed to clear cart: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Error clearing cart',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cart count for authenticated user.
     */
    public function cartCount()
    {
        $user = Auth::user();

        $count = ShoppingCart::where('created_by', $user->id)->sum('selected_quantity');

        return response()->json(['count' => $count]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Product;
use App\Traits\LoggableTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductReviewController extends Controller
{
    use LoggableTrait;

    public function index(Request $request)
    {
        $query = ProductReview::with(['product', 'user', 'createdBy', 'updatedBy']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->query('min_rating'));
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            return response()->json(['data' => $query->latest()->paginate($perPage)]);
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function show($id)
    {
        $review = ProductReview::with(['product', 'user', 'createdBy', 'updatedBy'])->find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return response()->json($review);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'product_id' => 'required|exists:products,id',
                'user_id' => 'required|exists:users,id',
                'rating' => 'required|numeric|min:0|max:5',
                'comment' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $user = Auth::user();

            $validatedData['created_by'] = $user->id;
            $validatedData['updated_by'] = $user->id;

            $review = ProductReview::create($validatedData);

            // Update product rating
            $this->updateProductRating($validatedData['product_id']);

            DB::commit();

            $this->logActivity('Product Review Created', [
                'review_id' => $review->id,
                'product_id' => $review->product_id,
                'created_by' => $user->name,
            ]);

            return response()->json([
                'message' => 'Review created successfully',
                'data' => $review->load(['product', 'user'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            $this->logActivity('Product Review Creation Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'An error occurred while creating review.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $review = ProductReview::findOrFail($id);

            $validatedData = $request->validate([
                'rating' => 'sometimes|numeric|min:0|max:5',
                'comment' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $user = Auth::user();

            $validatedData['updated_by'] = $user->id;
            $review->update($validatedData);

            // Update product rating
            $this->updateProductRating($review->product_id);

            DB::commit();

            $this->logActivity('Product Review Updated', [
                'review_id' => $review->id,
                'product_id' => $review->product_id,
                'updated_by' => $user->name,
            ]);

            return response()->json([
                'message' => 'Review updated successfully',
                'data' => $review->load(['product', 'user'])
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            $this->logActivity('Product Review Update Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'review_id' => $id,
            ]);

            return response()->json([
                'message' => 'An error occurred while updating review.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $review = ProductReview::findOrFail($id);
            $productId = $review->product_id;

            $review->delete();

            // Update product rating
            $this->updateProductRating($productId);

            $this->logActivity('Product Review Deleted', [
                'review_id' => $review->id,
                'product_id' => $productId,
                'deleted_by' => Auth::user()->name ?? 'System',
            ]);

            DB::commit();
            return response()->json(['message' => 'Review deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $itemsToDelete = $request->input('itemsToDelete');

        if (!is_array($itemsToDelete) || empty($itemsToDelete)) {
            return response()->json(['message' => 'Invalid or empty review data'], 400);
        }

        $reviewIds = array_column($itemsToDelete, 'id');

        if (empty($reviewIds)) {
            return response()->json(['message' => 'No valid review IDs found'], 400);
        }

        $reviews = ProductReview::whereIn('id', $reviewIds)->get();

        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'No matching reviews found'], 404);
        }

        DB::beginTransaction();

        try {
            $productIds = $reviews->pluck('product_id')->unique();

            foreach ($reviews as $review) {
                $review->delete();
            }

            // Update product ratings for all affected products
            foreach ($productIds as $productId) {
                $this->updateProductRating($productId);
            }

            $this->logActivity('Product Reviews Bulk Deleted', [
                'deleted_by' => Auth::user()->name ?? 'System',
                'count' => count($reviewIds),
            ]);

            DB::commit();

            return response()->json(['message' => 'Reviews deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            $this->logActivity('Product Reviews Bulk Delete Failed', [
                'review_ids' => $reviewIds,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'An error occurred while deleting reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function updateProductRating($productId)
    {
        $avgRating = ProductReview::where('product_id', $productId)->avg('rating');
        Product::where('id', $productId)->update(['rating' => $avgRating ?? 0]);
    }
}

<?php

/**
 * 🧠 AI GUIDELINE: Product Controller
 * ============================================================
 * Manages products with multiple photo uploads
 * - Uses HandlePhotoTrait for photo management
 * - Photos stored in year/month folders (product_photos/2025/January/)
 * - Supports featured photo (only one per product)
 * - Activity logging via LoggableTrait
 * - Standard CRUD + bulkDestroy operations
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Traits\HandlePhotoTrait;
use App\Traits\LoggableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    use HandlePhotoTrait, LoggableTrait;

    public function index(Request $request)
    {
        $query = Product::with(['subcategory.category', 'photos', 'featuredPhoto', 'createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('product_subcategory_id')) {
            $query->where('product_subcategory_id', $request->query('product_subcategory_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Products retrieved successfully',
                'data' => $data,
                'version' => 'v1',
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Products retrieved successfully',
            'data' => $data,
            'version' => 'v1',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:0',
                'rating' => 'nullable|numeric|min:0|max:5',
                'discount' => 'nullable|numeric|min:0|max:100',
                'status' => 'required|in:active,inactive',
                'product_subcategory_id' => 'required|exists:product_subcategories,id',
                'photos' => 'nullable|array',
                'photos.*.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
                'photos.*.featured' => 'nullable|string|in:true,false,1,0',
            ]);

            DB::beginTransaction();

            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            // Create product (remove photos from validated data as they're handled separately)
            $productData = $validated;
            unset($productData['photos']);
            $product = Product::create($productData);

            // Handle multiple photo uploads
            if ($request->has('photos') && is_array($request->input('photos'))) {
                $photos = $request->input('photos');

                foreach ($photos as $index => $photoData) {
                    if ($request->hasFile("photos.{$index}.file_path")) {
                        $photoUrl = $this->handlePhotoUpload($request->file("photos.{$index}.file_path"), 'product_photos', true);

                        if ($photoUrl) {
                            // Convert string 'true'/'false' to boolean
                            $featured = isset($photoData['featured']) && in_array($photoData['featured'], ['true', '1', 1, true], true);

                            ProductPhoto::create([
                                'product_id' => $product->id,
                                'photo_url' => $photoUrl,
                                'featured' => $featured,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            $this->logActivity('product_created', "Product '{$product->name}' created.", [
                'product_id' => $product->id,
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Product created successfully',
                    'data' => $product->load(['subcategory.category', 'photos', 'featuredPhoto']),
                    'version' => 'v1',
                ],
                201
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422
            );
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    public function show($id)
    {
        $product = Product::with(['subcategory.category', 'photos', 'featuredPhoto', 'createdBy', 'updatedBy'])->find($id);

        if (! $product) {
            return response()->json(
                [
                    'message' => 'Product not found',
                    'error' => 'not_found',
                    'code' => 404,
                    'version' => 'v1',
                ],
                404
            );
        }

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => $product,
            'version' => 'v1',
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (! $product) {
                return response()->json(
                    [
                        'message' => 'Product not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|numeric|min:0',
                'quantity' => 'sometimes|integer|min:0',
                'rating' => 'nullable|numeric|min:0|max:5',
                'discount' => 'nullable|numeric|min:0|max:100',
                'status' => 'sometimes|in:active,inactive',
                'product_subcategory_id' => 'sometimes|exists:product_subcategories,id',
                'photos' => 'nullable|array',
                'photos.*.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
                'photos.*.featured' => 'nullable|string|in:true,false,1,0',
                'delete_photo_ids' => 'nullable|array',
                'delete_photo_ids.*' => 'exists:product_photos,id',
            ]);

            DB::beginTransaction();

            $validated['updated_by'] = Auth::id();

            // Update product basic info (remove photos from validated data)
            $productData = $validated;
            unset($productData['photos'], $productData['delete_photo_ids']);
            $product->update($productData);

            // Handle photo deletion
            if ($request->filled('delete_photo_ids')) {
                $photosToDelete = ProductPhoto::whereIn('id', $request->input('delete_photo_ids'))
                    ->where('product_id', $product->id)
                    ->get();

                foreach ($photosToDelete as $photo) {
                    $this->deletePhoto($photo->photo_url);
                    $photo->delete();
                }
            }

            // Handle new photo uploads
            if ($request->has('photos') && is_array($request->input('photos'))) {
                $photos = $request->input('photos');
                $hasFeaturedPhoto = false;

                // Check if any new photo is marked as featured
                foreach ($photos as $index => $photoData) {
                    if (isset($photoData['featured']) && in_array($photoData['featured'], ['true', '1', 1, true], true)) {
                        $hasFeaturedPhoto = true;
                        break;
                    }
                }

                // If a new photo is featured, unfeature all existing photos
                if ($hasFeaturedPhoto) {
                    ProductPhoto::where('product_id', $product->id)->update(['featured' => false]);
                }

                foreach ($photos as $index => $photoData) {
                    if ($request->hasFile("photos.{$index}.file_path")) {
                        $photoUrl = $this->handlePhotoUpload($request->file("photos.{$index}.file_path"), 'product_photos', true);

                        if ($photoUrl) {
                            // Convert string 'true'/'false' to boolean
                            $featured = isset($photoData['featured']) && in_array($photoData['featured'], ['true', '1', 1, true], true);

                            ProductPhoto::create([
                                'product_id' => $product->id,
                                'photo_url' => $photoUrl,
                                'featured' => $featured,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            $this->logActivity('product_updated', "Product '{$product->name}' updated.", [
                'product_id' => $product->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $product->load(['subcategory.category', 'photos', 'featuredPhoto']),
                'version' => 'v1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422
            );
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::with('photos')->find($id);

            if (! $product) {
                return response()->json(
                    [
                        'message' => 'Product not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            DB::beginTransaction();

            // Delete all product photos
            foreach ($product->photos as $photo) {
                $this->deletePhoto($photo->photo_url);
                $photo->delete();
            }

            $productName = $product->name;
            $product->delete();

            $this->logActivity('product_deleted', "Product '{$productName}' deleted.", [
                'product_id' => $id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Product deleted successfully',
                'version' => 'v1',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    public function bulkDestroy(Request $request)
    {
        $items = $request->input('itemsToDelete');

        if (! is_array($items) || empty($items)) {
            return response()->json(
                [
                    'message' => 'Invalid or empty data',
                    'error' => 'bad_request',
                    'code' => 400,
                    'version' => 'v1',
                ],
                400
            );
        }

        $ids = array_column($items, 'id');

        try {
            DB::beginTransaction();

            $products = Product::with('photos')->whereIn('id', $ids)->get();

            if ($products->isEmpty()) {
                return response()->json(
                    [
                        'message' => 'No matching products found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            // Delete all photos for each product
            foreach ($products as $product) {
                foreach ($product->photos as $photo) {
                    $this->deletePhoto($photo->photo_url);
                    $photo->delete();
                }
            }

            $deletedNames = $products->pluck('name')->toArray();
            Product::whereIn('id', $ids)->delete();

            $this->logActivity('products_bulk_deleted', 'Deleted: ' . implode(', ', $deletedNames), [
                'ids' => $ids,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bulk delete successful',
                'version' => 'v1',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    /**
     * Update featured photo for a product
     */
    public function updateFeaturedPhoto(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (! $product) {
                return response()->json(
                    [
                        'message' => 'Product not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            $validated = $request->validate([
                'photo_id' => 'required|exists:product_photos,id',
            ]);

            DB::beginTransaction();

            // Verify photo belongs to this product
            $photo = ProductPhoto::where('id', $validated['photo_id'])
                ->where('product_id', $id)
                ->first();

            if (! $photo) {
                return response()->json(
                    [
                        'message' => 'Photo does not belong to this product',
                        'error' => 'invalid_photo',
                        'code' => 400,
                        'version' => 'v1',
                    ],
                    400
                );
            }

            // Unfeature all photos for this product
            ProductPhoto::where('product_id', $id)->update(['featured' => false]);

            // Feature the selected photo
            $photo->update(['featured' => true]);

            $this->logActivity('product_featured_photo_updated', "Featured photo updated for product '{$product->name}'.", [
                'product_id' => $product->id,
                'photo_id' => $photo->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Featured photo updated successfully',
                'data' => $product->load(['photos', 'featuredPhoto']),
                'version' => 'v1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422
            );
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }
}

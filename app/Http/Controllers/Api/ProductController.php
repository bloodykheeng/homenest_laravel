<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttachment;
use App\Traits\HandleAttachmentTrait;
use App\Traits\LoggableTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use HandleAttachmentTrait, LoggableTrait;

    public function index(Request $request)
    {
        $query = Product::with([
            'subcategory',
            'category',
            'productAttachments',
            'featuredAttachment',
            'createdBy',
            'updatedBy',
        ]);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('product_subcategory_id')) {
            $query->where('product_subcategory_id', $request->query('product_subcategory_id'));
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->query('min_price'));
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->query('min_rating'));
        }

        if ($request->has('in_stock') && $request->boolean('in_stock')) {
            $query->where('quantity', '>', 0);
        }

        if ($request->has('on_discount') && $request->boolean('on_discount')) {
            $query->where('discount', '>', 0);
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            return response()->json(['data' => $query->latest()->paginate($perPage)]);
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function show($id)
    {
        $product = Product::with([
            'subcategory',
            'category',
            'productAttachments',
            'featuredAttachment',
            'createdBy',
            'updatedBy',
        ])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:0',
                'rating' => 'nullable|numeric|min:0|max:5',
                'discount' => 'nullable|numeric|min:0|max:100',
                'status' => 'required|in:active,inactive,out_of_stock',
                'product_subcategory_id' => 'required|exists:product_subcategories,id',

                // Attachments
                'attachments' => 'nullable|array',
                'attachments.*.type' => 'nullable|string',
                'attachments.*.caption' => 'nullable|string',
                'attachments.*.featured' => 'nullable|boolean',
                'attachments.*.file_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,mkv,avi,pdf',
            ]);

            DB::beginTransaction();

            $user = Auth::user();

            $validatedData['created_by'] = $user->id;
            $validatedData['updated_by'] = $user->id;

            $product = Product::create($validatedData);

            // Handle attachments
            if (isset($validatedData['attachments'])) {
                $hasFeatured = false;

                foreach ($validatedData['attachments'] as $index => $attachmentData) {
                    if ($request->hasFile("attachments.$index.file_path")) {
                        $uploadedFile = $request->file("attachments.$index.file_path");
                        $fileData = $this->handleAttachmentUpload($uploadedFile, 'product_attachments');

                        // Ensure only one featured attachment
                        $isFeatured = ($attachmentData['featured'] ?? false) && !$hasFeatured;
                        if ($isFeatured) {
                            $hasFeatured = true;
                        }

                        ProductAttachment::create([
                            'product_id' => $product->id,
                            'type' => $attachmentData['type'] ?? null,
                            'file_path' => $fileData['file_path'],
                            'caption' => $attachmentData['caption'] ?? null,
                            'featured' => $isFeatured,
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                        ]);
                    }
                }
            }

            DB::commit();

            $this->logActivity('Product Created', [
                'product_id' => $product->id,
                'name' => $product->name,
                'created_by' => $user->name,
            ]);

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product->load(['subcategory', 'productAttachments', 'featuredAttachment'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Product Creation Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'An error occurred while creating product.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::with(['productAttachments'])->findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|numeric|min:0',
                'quantity' => 'sometimes|integer|min:0',
                'rating' => 'nullable|numeric|min:0|max:5',
                'discount' => 'nullable|numeric|min:0|max:100',
                'status' => 'sometimes|in:active,inactive,out_of_stock',
                'product_subcategory_id' => 'sometimes|exists:product_subcategories,id',

                // Attachments
                'attachments' => 'nullable|array',
                'attachments.*.existing_attachment_id' => 'nullable|exists:product_attachments,id',
                'attachments.*.status' => 'nullable|string',
                'attachments.*.type' => 'nullable|string',
                'attachments.*.caption' => 'nullable|string',
                'attachments.*.featured' => 'nullable|boolean',
                'attachments.*.file_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,mkv,avi,pdf',
            ]);

            DB::beginTransaction();

            $user = Auth::user();

            $validatedData['updated_by'] = $user->id;
            $product->update($validatedData);

            // Handle attachments
            if (isset($validatedData['attachments'])) {
                $existingAttachmentIds = collect($validatedData['attachments'])
                    ->whereNotNull('existing_attachment_id')
                    ->pluck('existing_attachment_id')
                    ->toArray();

                // Delete removed attachments
                $product->productAttachments()
                    ->whereNotIn('id', $existingAttachmentIds)
                    ->each(function ($attachment) {
                        $this->deleteAttachment($attachment->getRawOriginal('file_path'));
                        $attachment->delete();
                    });

                $hasFeatured = false;

                foreach ($validatedData['attachments'] as $index => $attachmentData) {
                    // Update existing attachment
                    if (isset($attachmentData['existing_attachment_id']) && $attachmentData['status'] !== 'new') {
                        $existingAttachment = ProductAttachment::find($attachmentData['existing_attachment_id']);
                        if ($existingAttachment) {
                            // Ensure only one featured attachment
                            $isFeatured = ($attachmentData['featured'] ?? false) && !$hasFeatured;
                            if ($isFeatured) {
                                $hasFeatured = true;
                                // Unfeatured all others
                                $product->productAttachments()
                                    ->where('id', '!=', $existingAttachment->id)
                                    ->update(['featured' => false]);
                            }

                            $existingAttachment->update([
                                'type' => $attachmentData['type'] ?? $existingAttachment->type,
                                'caption' => $attachmentData['caption'] ?? $existingAttachment->caption,
                                'featured' => $isFeatured,
                                'updated_by' => $user->id,
                            ]);
                        }
                    }

                    // Create new attachment
                    if ($attachmentData['status'] === 'new' && $request->hasFile("attachments.$index.file_path")) {
                        $uploadedFile = $request->file("attachments.$index.file_path");
                        $fileData = $this->handleAttachmentUpload($uploadedFile, 'product_attachments');

                        // Ensure only one featured attachment
                        $isFeatured = ($attachmentData['featured'] ?? false) && !$hasFeatured;
                        if ($isFeatured) {
                            $hasFeatured = true;
                            // Unfeatured all others
                            $product->productAttachments()->update(['featured' => false]);
                        }

                        ProductAttachment::create([
                            'product_id' => $product->id,
                            'type' => $attachmentData['type'] ?? null,
                            'file_path' => $fileData['file_path'],
                            'caption' => $attachmentData['caption'] ?? null,
                            'featured' => $isFeatured,
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                        ]);
                    }
                }
            }

            DB::commit();

            $this->logActivity('Product Updated', [
                'product_id' => $product->id,
                'name' => $product->name,
                'updated_by' => $user->name,
            ]);

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $product->load(['subcategory', 'productAttachments', 'featuredAttachment'])
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Product Update Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'product_id' => $id,
            ]);

            return response()->json([
                'message' => 'An error occurred while updating product.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete all attachments
            foreach ($product->productAttachments as $attachment) {
                $this->deleteAttachment($attachment->getRawOriginal('file_path'));
            }

            $product->delete();

            $this->logActivity('Product Deleted', [
                'product_id' => $product->id,
                'name' => $product->name,
                'deleted_by' => Auth::user()->name ?? 'System',
            ]);

            DB::commit();
            return response()->json(['message' => 'Product deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $itemsToDelete = $request->input('itemsToDelete');

        if (!is_array($itemsToDelete) || empty($itemsToDelete)) {
            return response()->json(['message' => 'Invalid or empty product data'], 400);
        }

        $productIds = array_column($itemsToDelete, 'id');

        if (empty($productIds)) {
            return response()->json(['message' => 'No valid product IDs found'], 400);
        }

        $products = Product::whereIn('id', $productIds)
            ->with(['productAttachments'])
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No matching products found'], 404);
        }

        $deletedProductDetails = [];

        DB::beginTransaction();

        try {
            foreach ($products as $product) {
                $deletedProductDetails[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                ];

                // Delete attachments
                foreach ($product->productAttachments as $attachment) {
                    $this->deleteAttachment($attachment->getRawOriginal('file_path'));
                    $attachment->delete();
                }

                // Delete the product
                $product->delete();
            }

            $detailsString = collect($deletedProductDetails)
                ->map(fn($product) => "Product ID: {$product['id']}, Name: \"{$product['name']}\"")
                ->join('; ');

            $this->logActivity('Products Bulk Deleted', [
                'deleted_by' => Auth::user()->name ?? 'System',
                'details' => $detailsString,
                'count' => count($deletedProductDetails),
            ]);

            DB::commit();

            return response()->json(['message' => 'Products deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Products Bulk Delete Failed', [
                'product_ids' => $productIds,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'An error occurred while deleting products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

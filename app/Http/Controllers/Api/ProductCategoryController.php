<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Traits\HandlePhotoTrait;
use App\Traits\LoggableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductCategoryController extends Controller
{
    use HandlePhotoTrait, LoggableTrait;

    public function index(Request $request)
    {
        $query = ProductCategory::with(['createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Categories retrieved successfully',
                'data' => $data,
                'version' => 'v1',
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $data,
            'version' => 'v1',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:product_categories,name',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive',
                'photo' => 'nullable|array',
                'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            DB::beginTransaction();

            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            // Handle photo upload
            if ($request->hasFile('photo.file_path')) {
                $validated['photo_url'] = $this->handlePhotoUpload($request->file('photo.file_path'), 'categories', false);
            }

            $category = ProductCategory::create($validated);

            $this->logActivity('category_created', "Category '{$category->name}' created.", [
                'category_id' => $category->id,
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Category created successfully',
                    'data' => $category,
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
        $category = ProductCategory::with(['createdBy', 'updatedBy', 'subcategories'])->find($id);

        if (! $category) {
            return response()->json(
                [
                    'message' => 'Category not found',
                    'error' => 'not_found',
                    'code' => 404,
                    'version' => 'v1',
                ],
                404
            );
        }

        return response()->json([
            'message' => 'Category retrieved successfully',
            'data' => $category,
            'version' => 'v1',
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $category = ProductCategory::find($id);

            if (! $category) {
                return response()->json(
                    [
                        'message' => 'Category not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:product_categories,name,' . $category->id,
                'description' => 'nullable|string',
                'status' => 'sometimes|in:active,inactive',
                'photo' => 'nullable|array',
                'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            DB::beginTransaction();

            $validated['updated_by'] = Auth::id();

            // Handle photo update
            if ($request->hasFile('photo.file_path')) {
                $validated['photo_url'] = $this->updatePhoto($request->file('photo.file_path'), $category->photo_url, 'categories', false);
            }

            $category->update($validated);

            $this->logActivity('category_updated', "Category '{$category->name}' updated.", [
                'category_id' => $category->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $category,
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
            $category = ProductCategory::find($id);

            if (! $category) {
                return response()->json(
                    [
                        'message' => 'Category not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            DB::beginTransaction();

            // Delete photo if exists
            if ($category->photo_url) {
                $this->deletePhoto($category->photo_url);
            }

            $categoryName = $category->name;
            $category->delete();

            $this->logActivity('category_deleted', "Category '{$categoryName}' deleted.", [
                'category_id' => $id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Category deleted successfully',
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

            $categories = ProductCategory::whereIn('id', $ids)->get();

            if ($categories->isEmpty()) {
                return response()->json(
                    [
                        'message' => 'No matching categories found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            // Delete photos
            foreach ($categories as $category) {
                if ($category->photo_url) {
                    $this->deletePhoto($category->photo_url);
                }
            }

            $deletedNames = $categories->pluck('name')->toArray();
            ProductCategory::whereIn('id', $ids)->delete();

            $this->logActivity('categories_bulk_deleted', 'Deleted: ' . implode(', ', $deletedNames), [
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
}

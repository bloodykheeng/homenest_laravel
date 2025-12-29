<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductSubcategory;
use App\Traits\HandlePhotoTrait;
use App\Traits\LoggableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductSubcategoryController extends Controller
{
    use HandlePhotoTrait, LoggableTrait;

    public function index(Request $request)
    {
        $query = ProductSubcategory::with(['category', 'createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('product_category_id')) {
            $query->where('product_category_id', $request->query('product_category_id'));
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Subcategories retrieved successfully',
                'data' => $data,
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Subcategories retrieved successfully',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive',
                'product_category_id' => 'required|exists:product_categories,id',
                'photo' => 'nullable|array',
                'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            DB::beginTransaction();

            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            // Handle photo upload
            if ($request->hasFile('photo.file_path')) {
                $validated['photo_url'] = $this->handlePhotoUpload($request->file('photo.file_path'), 'subcategories', false);
            }

            $subcategory = ProductSubcategory::create($validated);

            $this->logActivity('subcategory_created', "Subcategory '{$subcategory->name}' created.", [
                'subcategory_id' => $subcategory->id,
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Subcategory created successfully',
                    'data' => $subcategory->load('category'),
                ],
                201
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed: ' . $e->getMessage(),
                    'errors' => $e->errors(),
                    'code' => 422,
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

                ],
                500
            );
        }
    }

    public function show($id)
    {
        $subcategory = ProductSubcategory::with(['category', 'createdBy', 'updatedBy', 'products'])->find($id);

        if (! $subcategory) {
            return response()->json(
                [
                    'message' => 'Subcategory not found',
                    'error' => 'not_found',
                    'code' => 404,

                ],
                404
            );
        }

        return response()->json([
            'message' => 'Subcategory retrieved successfully',
            'data' => $subcategory
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $subcategory = ProductSubcategory::find($id);

            if (! $subcategory) {
                return response()->json(
                    [
                        'message' => 'Subcategory not found',
                        'error' => 'not_found',
                        'code' => 404,

                    ],
                    404
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|in:active,inactive',
                'product_category_id' => 'sometimes|exists:product_categories,id',
                'photo' => 'nullable|array',
                'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            DB::beginTransaction();

            $validated['updated_by'] = Auth::id();

            // Handle photo update
            if ($request->hasFile('photo.file_path')) {
                $validated['photo_url'] = $this->updatePhoto($request->file('photo.file_path'), $subcategory->photo_url, 'subcategories', false);
            }

            $subcategory->update($validated);

            $this->logActivity('subcategory_updated', "Subcategory '{$subcategory->name}' updated.", [
                'subcategory_id' => $subcategory->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Subcategory updated successfully',
                'data' => $subcategory->load('category'),
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed: ' . $e->getMessage(),
                    'errors' => $e->errors(),
                    'code' => 422,
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

                ],
                500
            );
        }
    }

    public function destroy($id)
    {
        try {
            $subcategory = ProductSubcategory::find($id);

            if (! $subcategory) {
                return response()->json(
                    [
                        'message' => 'Subcategory not found',
                        'error' => 'not_found',
                        'code' => 404,

                    ],
                    404
                );
            }

            DB::beginTransaction();

            // Delete photo if exists
            if ($subcategory->photo_url) {
                $this->deletePhoto($subcategory->photo_url);
            }

            $subcategoryName = $subcategory->name;
            $subcategory->delete();

            $this->logActivity('subcategory_deleted', "Subcategory '{$subcategoryName}' deleted.", [
                'subcategory_id' => $id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Subcategory deleted successfully',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,

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

                ],
                400
            );
        }

        $ids = array_column($items, 'id');

        try {
            DB::beginTransaction();

            $subcategories = ProductSubcategory::whereIn('id', $ids)->get();

            if ($subcategories->isEmpty()) {
                return response()->json(
                    [
                        'message' => 'No matching subcategories found',
                        'error' => 'not_found',
                        'code' => 404,

                    ],
                    404
                );
            }

            // Delete photos
            foreach ($subcategories as $subcategory) {
                if ($subcategory->photo_url) {
                    $this->deletePhoto($subcategory->photo_url);
                }
            }

            $deletedNames = $subcategories->pluck('name')->toArray();
            ProductSubcategory::whereIn('id', $ids)->delete();

            $this->logActivity('subcategories_bulk_deleted', 'Deleted: ' . implode(', ', $deletedNames), [
                'ids' => $ids,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bulk delete successful',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,

                ],
                500
            );
        }
    }
}

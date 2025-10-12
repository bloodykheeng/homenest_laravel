<?php
/**
 * 🧠 AI GUIDELINE: Versioned Laravel API Controller Structure
 * ============================================================
 * This controller is versioned (v1) and follows API best practices:
 * - Request validation
 * - DB transactions for multi-model operations
 * - Try/catch error handling
 * - Standardized JSON responses
 * - Activity logging via LoggableTrait
 * - Clean "not found" handling
 * - Filters & pagination in index()
 *
 * 📂 Location: App\Http\Controllers\Api\V1
 * 📌 Naming: {ModelName}Controller.php
 * 🧭 Routes example:
 *    Route::prefix('v1')->group(function () {
 *        Route::apiResource('models', ModelController::class);
 *        Route::post('models/bulk-destroy', [ModelController::class, 'bulkDestroy']);
 *    });
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Traits\LoggableTrait;
use App\Models\ModelName; // 📝 Replace with your actual model

class ModelController extends Controller
{
    use LoggableTrait;

    /**
     * 📜 index() - List resources with optional filters and pagination.
     */
    public function index(Request $request)
    {
        $query = ModelName::with(['createdBy', 'updatedBy']);

        // 🔍 Filters
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('startDate')) {
            $query->whereDate('created_at', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('created_at', '<=', $request->query('endDate'));
        }

        // 📊 Pagination
        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Data retrieved successfully',
                'data' => $data,
                'version' => 'v1',
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Data retrieved successfully',
            'data' => $data,
            'version' => 'v1',
        ]);
    }

    /**
     * 🆕 store() - Create a new resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:model_names,name',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        try {
            DB::beginTransaction();

            $model = ModelName::create($validated);

            $this->logActivity('model_created', "Model '{$model->name}' created.", [
                'model_id' => $model->id,
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Resource created successfully',
                    'data' => $model,
                    'version' => 'v1',
                ],
                201,
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422,
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }

    /**
     * 👁️ show() - Display a single resource.
     */
    public function show($id)
    {
        $model = ModelName::find($id);

        if (!$model) {
            return response()->json(
                [
                    'message' => 'The requested resource was not found',
                    'error' => 'not_found',
                    'code' => 404,
                    'version' => 'v1',
                ],
                404,
            );
        }

        return response()->json([
            'message' => 'Resource retrieved successfully',
            'data' => $model,
            'version' => 'v1',
        ]);
    }

    /**
     * ✍️ update() - Update an existing resource.
     */
    public function update(Request $request, $id)
    {
        try {
            $model = ModelName::find($id);

            if (!$model) {
                return response()->json(
                    [
                        'message' => 'The requested resource was not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404,
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:model_names,name,' . $model->id,
                'status' => 'sometimes|string|in:active,inactive',
            ]);

            $validated['updated_by'] = Auth::id();

            DB::beginTransaction();
            $model->update($validated);

            $this->logActivity('model_updated', "Model '{$model->name}' updated.", [
                'model_id' => $model->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Resource updated successfully',
                'data' => $model,
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
                422,
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }

    /**
     * 🗑️ destroy() - Delete a resource.
     */
    public function destroy($id)
    {
        try {
            $model = ModelName::find($id);

            if (!$model) {
                return response()->json(
                    [
                        'message' => 'The requested resource was not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404,
                );
            }

            DB::beginTransaction();
            $model->delete();

            $this->logActivity('model_deleted', "Model '{$model->name}' deleted.", [
                'model_id' => $model->id,
            ]);
            DB::commit();

            return response()->json([
                'message' => 'Resource deleted successfully',
                'version' => 'v1',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }

    /**
     * 🧹 bulkDestroy() - Delete multiple resources at once.
     */
    public function bulkDestroy(Request $request)
    {
        $items = $request->input('itemsToDelete');

        if (!is_array($items) || empty($items)) {
            return response()->json(
                [
                    'message' => 'Invalid or empty data',
                    'error' => 'bad_request',
                    'code' => 400,
                    'version' => 'v1',
                ],
                400,
            );
        }

        $ids = array_column($items, 'id');

        try {
            DB::beginTransaction();

            $models = ModelName::whereIn('id', $ids)->get();

            if ($models->isEmpty()) {
                return response()->json(
                    [
                        'message' => 'No matching resources found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404,
                );
            }

            $deletedNames = $models->pluck('name')->toArray();
            ModelName::whereIn('id', $ids)->delete();

            $this->logActivity('models_bulk_deleted', 'Deleted: ' . implode(', ', $deletedNames), [
                'ids' => $ids,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bulk delete successful',
                'version' => 'v1',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }
}

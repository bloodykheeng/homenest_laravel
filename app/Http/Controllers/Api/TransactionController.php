<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Traits\LoggableTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    use LoggableTrait;

    private function transactionRelations(): array
    {
        return [
            'order',
            'createdBy',
            'updatedBy',
        ];
    }

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $query = Transaction::with($this->transactionRelations());

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->query('order_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', 'like', '%'.$request->query('payment_method').'%');
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->query('transaction_type'));
        }

        if ($request->filled('olycash_purchase_id')) {
            $query->where('olycash_purchase_id', $request->query('olycash_purchase_id'));
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
                'message' => 'Transactions retrieved successfully',
                'data' => $data,
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Transactions retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * Store a manually created transaction against an order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|max:10',
            'status' => 'sometimes|string|in:pending,initiated,success,failed',
            'notes' => 'sometimes|nullable|string',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'order_id' => $validated['order_id'],
                'transaction_type' => 'manual',
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'UGX',
                'status' => $validated['status'] ?? 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            DB::commit();

            $transaction->load($this->transactionRelations());

            $this->logActivity('Transaction Created', "Manual transaction created for order #{$validated['order_id']}.", [
                'transaction_id' => $transaction->id,
                'order_id' => $transaction->order_id,
            ]);

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => $transaction,
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
     * Display the specified transaction.
     */
    public function show($id)
    {
        $transaction = Transaction::with($this->transactionRelations())->find($id);

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaction not found',
                'error' => 'not_found',
            ], 404);
        }

        return response()->json([
            'message' => 'Transaction retrieved successfully',
            'data' => $transaction,
        ]);
    }

    /**
     * Update a transaction (admin can manually adjust status/notes).
     */
    public function update(Request $request, $id)
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaction not found',
                'error' => 'not_found',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,initiated,success,failed',
            'payment_method' => 'sometimes|string|max:100',
            'notes' => 'sometimes|nullable|string',
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|max:10',
        ]);

        $validated['updated_by'] = Auth::id();

        DB::beginTransaction();
        try {
            $transaction->update($validated);
            DB::commit();

            $transaction->load($this->transactionRelations());

            $this->logActivity('Transaction Updated', "Transaction #{$transaction->id} updated.", [
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'message' => 'Transaction updated successfully',
                'data' => $transaction,
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
     * Remove the specified transaction.
     */
    public function destroy($id)
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaction not found',
                'error' => 'not_found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $transaction->delete();
            DB::commit();

            $this->logActivity('Transaction Deleted', "Transaction #{$id} deleted.", [
                'transaction_id' => $id,
            ]);

            return response()->json(['message' => 'Transaction deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete transactions.
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
            $transactions = Transaction::whereIn('id', $ids)->get();

            if ($transactions->isEmpty()) {
                return response()->json([
                    'message' => 'No matching transactions found',
                    'error' => 'not_found',
                ], 404);
            }

            Transaction::whereIn('id', $ids)->delete();
            DB::commit();

            $this->logActivity('Transactions Bulk Deleted', 'Bulk deleted transactions.', ['ids' => $ids]);

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

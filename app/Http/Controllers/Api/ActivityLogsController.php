<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\LoggableTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;


class ActivityLogsController extends Controller
{
    use LoggableTrait;

    public function index(Request $request)
    {
        $query = Activity::query();

        // Eager load related models
        $query->with(["causer", "subject"]);

        // Filter by log name
        if ($request->filled('logName')) {
            $query->where('log_name', $request->query('logName'));
        }

        // Filter by search term (description)
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->query('search') . '%');
        }

        // Get start and end dates
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        // Apply the startDate filter if it's set and valid
        if (isset($startDate) && $this->isValidDate($startDate)) {
            $formattedStartDate = Carbon::parse($startDate)->format('Y-m-d');
            $query->whereDate('created_at', '>=', $formattedStartDate);
        }

        // Apply the endDate filter if it's set and valid
        if (isset($endDate) && $this->isValidDate($endDate)) {
            $formattedEndDate = Carbon::parse($endDate)->format('Y-m-d');
            $query->whereDate('created_at', '<=', $formattedEndDate);
        }

        // Handle pagination
        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            return response()->json(['data' => $query->latest()->paginate($perPage)]);
            // return response()->json(['data' => $query->paginate($perPage)]);
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    // Helper function to check if a date is valid
    private function isValidDate($date)
    {
        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    ///
    public function bulkDestroy(Request $request)
    {
        $itemsToDelete = $request->input('itemsToDelete');

        if (!is_array($itemsToDelete) || empty($itemsToDelete)) {
            return response()->json(['message' => 'Invalid or empty activity logs data'], 400);
        }

        $activityLogIds = array_column($itemsToDelete, 'id');

        if (empty($activityLogIds)) {
            return response()->json(['message' => 'No valid activity log IDs found'], 400);
        }

        $activities = Activity::whereIn('id', $activityLogIds)->get();

        if ($activities->isEmpty()) {
            return response()->json(['message' => 'No matching activity logs found'], 404);
        }

        $deletedDescriptions = $activities->pluck('description')->toArray();
        Activity::whereIn('id', $activityLogIds)->delete();

        $this->logActivity('audit_logs_bulk_deleted', "Deleted activity logs: " . implode(', ', $deletedDescriptions), [
            'activity_log_ids' => $activityLogIds,
        ]);

        return response()->json(['message' => 'Activity logs deleted successfully']);
    }
}

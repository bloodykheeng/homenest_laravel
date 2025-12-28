<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FailedJob;
use App\Models\Job;
use App\Traits\LoggableTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class JobController extends Controller
{

    use LoggableTrait;

    /**
     * Get filtered queued jobs.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueuedJobs(Request $request)
    {
        $query = Job::query();

        // Filter by queue name
        $queue = $request->query('queue');
        if (isset($queue)) {
            $query->where('queue', $queue);
        }

        // Filter by attempts
        $minAttempts = $request->query('min_attempts');
        if (isset($minAttempts)) {
            $query->where('attempts', '>=', $minAttempts);
        }

        // Filter by created_at date range (handling Unix timestamps)
        $startDate = $request->query('startDate');
        if (isset($startDate)) {
            // Convert startDate (normal date format) to Unix timestamp
            $startDate = Carbon::parse($startDate)->startOfDay()->timestamp;
            $query->where('created_at', '>=', $startDate);
        }

        $endDate = $request->query('endDate');
        if (isset($endDate)) {
            // Convert endDate (normal date format) to Unix timestamp
            $endDate = Carbon::parse($endDate)->endOfDay()->timestamp;
            $query->where('created_at', '<=', $endDate);
        }


        $query->latest();

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $jobs = $query->paginate($perPage);
            return response()->json(['data' => $jobs]);
        }

        // Get filtered results
        $jobs = $query->get();

        return response()->json(['data' => $jobs]);
    }

    /**
     * Get filtered failed jobs.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFailedJobs(Request $request)
    {
        $query = FailedJob::query();

        // Filter by queue name
        $queue = $request->query('queue');
        if (isset($queue)) {
            $query->where('queue', $queue);
        }

        // Filter by failed_at date range
        $startDate = $request->query('startDate');
        if (isset($startDate)) {
            $startDate = Carbon::parse($startDate)->startOfDay(); // Parse and set to the start of the day
            $query->where('failed_at', '>=', $startDate);
        }

        $endDate = $request->query('endDate');
        if (isset($endDate)) {
            $endDate = Carbon::parse($endDate)->endOfDay(); // Parse and set to the end of the day
            $query->where('failed_at', '<=', $endDate);
        }

        // Order by the latest failed jobs using 'failed_at' column
        $query->orderBy('failed_at', 'desc'); // Order by failed_at in descending order

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $failedJobs = $query->paginate($perPage);
            return response()->json(['data' => $failedJobs]);
        }

        // Get filtered results
        $failedJobs = $query->get();

        return response()->json(['data' => $failedJobs]);
    }

    /**
     * Get job statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobStats()
    {
        try {
            // Fetch counts for jobs and failed_jobs
            $queuedJobsCount = Job::count();
            $failedJobsCount = FailedJob::count();

            // Prepare statistics data
            $stats = [
                [
                    'name'        => 'Queued Jobs',
                    'description' => 'Total number of jobs currently in the queue.',
                    'value'       => $queuedJobsCount,
                ],
                [
                    'name'        => 'Failed Jobs',
                    'description' => 'Total number of jobs that have failed.',
                    'value'       => $failedJobsCount,
                ],
                [
                    'name'        => 'Total Jobs',
                    'description' => 'Total number of jobs (queued + failed).',
                    'value'       => $queuedJobsCount + $failedJobsCount,
                ],
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Job statistics retrieved successfully.',
                'data'    => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve job statistics.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete jobs.
     */
    public function bulkDestroyJobs(Request $request)
    {
        $itemsToDelete = $request->input('itemsToDelete');

        if (!is_array($itemsToDelete) || empty($itemsToDelete)) {
            return response()->json(['message' => 'Invalid or empty jobs data'], 400);
        }

        // Extract only the `id` values from the array of objects
        $jobIds = array_column($itemsToDelete, 'id');

        if (empty($jobIds)) {
            return response()->json(['message' => 'No valid job IDs found'], 400);
        }

        $jobs = Job::whereIn('id', $jobIds)->get();

        if ($jobs->isEmpty()) {
            return response()->json(['message' => 'No matching jobs found'], 404);
        }

        $deletedJobIds = $jobs->pluck('id')->toArray();
        Job::whereIn('id', $jobIds)->delete();

        $this->logActivity('jobs_bulk_deleted', "Jobs deleted: " . implode(', ', $deletedJobIds), ['job_ids' => $jobIds]);

        return response()->json(['message' => 'Jobs deleted successfully']);
    }

    /**
     * Bulk delete failed jobs.
     */
    public function bulkDestroyFailedJobs(Request $request)
    {
        $itemsToDelete = $request->input('itemsToDelete');

        if (!is_array($itemsToDelete) || empty($itemsToDelete)) {
            return response()->json(['message' => 'Invalid or empty failed jobs data'], 400);
        }

        // Extract only the `id` values from the array of objects
        $jobIds = array_column($itemsToDelete, 'id');

        if (empty($jobIds)) {
            return response()->json(['message' => 'No valid failed job IDs found'], 400);
        }

        $failedJobs = FailedJob::whereIn('id', $jobIds)->get();

        if ($failedJobs->isEmpty()) {
            return response()->json(['message' => 'No matching failed jobs found'], 404);
        }

        $deletedJobIds = $failedJobs->pluck('id')->toArray();
        FailedJob::whereIn('id', $jobIds)->delete();

        $this->logActivity('failed_jobs_bulk_deleted', "Failed jobs deleted: " . implode(', ', $deletedJobIds), ['failed_job_ids' => $jobIds]);

        return response()->json(['message' => 'Failed jobs deleted successfully']);
    }
}

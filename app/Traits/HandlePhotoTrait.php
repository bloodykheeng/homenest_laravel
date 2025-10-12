<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * 🧠 AI GUIDELINE: Photo Upload Handler Trait
 * ============================================================
 * Provides reusable methods for handling photo uploads with:
 * - Automatic folder organization by year/month for products
 * - Storage facade for file management
 * - Photo deletion functionality
 * - Unique filename generation
 */
trait HandlePhotoTrait
{
    /**
     * Handle photo upload
     *
     * @param  \Illuminate\Http\UploadedFile  $photo
     * @param  string  $folder  Base folder (e.g., 'product_photos', 'categories')
     * @param  bool  $useMonthFolder  Whether to organize by year/month (for products)
     * @return string|null Path to uploaded photo
     */
    public function handlePhotoUpload($photo, $folder, $useMonthFolder = false): ?string
    {
        if (! $photo || ! $photo->isValid()) {
            return null;
        }

        try {
            // Generate unique filename
            $filename = time().'_'.uniqid().'.'.$photo->getClientOriginalExtension();

            // Build storage path
            if ($useMonthFolder) {
                $year = Carbon::now()->format('Y');
                $month = Carbon::now()->format('F');
                $path = "{$folder}/{$year}/{$month}";
            } else {
                $path = $folder;
            }

            // Store the photo and return the path
            $storedPath = $photo->storeAs($path, $filename, 'public');

            return $storedPath;
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Delete photo from storage
     *
     * @param  string|null  $photoUrl
     */
    public function deletePhoto($photoUrl): bool
    {
        if (! $photoUrl) {
            return false;
        }

        try {
            // Remove 'storage/' prefix if present
            $photoPath = str_replace('storage/', '', $photoUrl);

            // Delete from storage
            if (Storage::disk('public')->exists($photoPath)) {
                return Storage::disk('public')->delete($photoPath);
            }

            return false;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Delete multiple photos from storage
     *
     * @return int Number of photos deleted
     */
    public function deleteMultiplePhotos(array $photoUrls): int
    {
        $deletedCount = 0;

        foreach ($photoUrls as $photoUrl) {
            if ($this->deletePhoto($photoUrl)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Update photo (delete old and upload new)
     *
     * @param  \Illuminate\Http\UploadedFile|null  $newPhoto
     * @param  string|null  $oldPhotoUrl
     * @param  string  $folder
     * @param  bool  $useMonthFolder
     */
    public function updatePhoto($newPhoto, $oldPhotoUrl, $folder, $useMonthFolder = false): ?string
    {
        // If no new photo, return the old one
        if (! $newPhoto) {
            return $oldPhotoUrl;
        }

        // Delete old photo if exists
        if ($oldPhotoUrl) {
            $this->deletePhoto($oldPhotoUrl);
        }

        // Upload new photo
        return $this->handlePhotoUpload($newPhoto, $folder, $useMonthFolder);
    }
}

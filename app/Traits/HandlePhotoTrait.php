<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Storage;

trait HandlePhotoTrait
{

    use LoggableTrait;
    /**
     * Handle photo upload and return the stored path.
     *
     * @param \Illuminate\Http\UploadedFile $photo
     * @param string $folder
     * @return string
     * @throws Exception
     */
    public function handlePhotoUpload($photo, $folder)
    {
        try {
            // Get the original name without extension
            $originalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);

            // Clean and shorten the name to max 30 chars
            $shortName = substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName), 0, 30);

            // Generate unique filename
            $filename = $shortName . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();

            // Store the file
            // Store the file (returns relative path e.g. "users/xxxx.jpg")
            return $photo->storeAs($folder, $filename, 'public');
        } catch (Exception $e) {
            throw new Exception('Failed to upload photo: ' . $e->getMessage());
        }
    }

    /**
     * Delete a photo if it exists.
     *
     * @param string|null $photoUrl
     * @return void
     */
    public function deletePhoto($photoUrl)
    {
        if (!$photoUrl) {
            return;
        }

        try {
            // Extract the file path from the URL
            $path = str_replace('/storage/', '', $photoUrl);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (Exception $e) {
            // If you have an activity logger, call it here
            if (method_exists($this, 'logActivity')) {
                $this->logActivity(
                    'photo_delete_failed',
                    "Failed to delete photo: {$photoUrl}. Error: {$e->getMessage()}",
                    ['photo_url' => $photoUrl]
                );
            }
        }
    }
}

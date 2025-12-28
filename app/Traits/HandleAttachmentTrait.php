<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait HandleAttachmentTrait
{
    /**
     * Handle attachment upload and return the stored path and file type.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $baseFolder
     * @param string $disk (default: 'public')
     * @return array ['file_path' => string, 'file_type' => string]
     * @throws Exception
     */
    public function handleAttachmentUpload($file, $baseFolder, $disk = 'public')
    {
        try {
            // Get MIME type and determine subfolder
            $mimeType = $file->getMimeType();
            $subFolder = $this->getSubFolderForMimeType($mimeType);

            // Build the full folder path
            $folderPath = $baseFolder . '/' . $subFolder;

            // Get the original name without extension
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Clean and shorten the name to max 30 chars
            $shortName = substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName), 0, 30);

            // Generate unique filename with timestamp
            $filename = $shortName . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store the file (returns relative path e.g. "feedback_attachments/images/xxxx.jpg")
            $filePath = $file->storeAs($folderPath, $filename, $disk);

            return [
                'file_path' => $filePath,
                'file_type' => $mimeType,
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to upload attachment: ' . $e->getMessage());
        }
    }

    /**
     * Determine the subfolder based on MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    private function getSubFolderForMimeType($mimeType)
    {
        $mimeTypeMap = [
            'images' => [
                'image/jpeg',
                'image/png',
                'image/jpg',
                'image/gif',
                'image/svg+xml',
                'image/webp',
                'image/heic',
            ],
            'videos' => [
                'video/mp4',
                'video/mpeg',
                'video/quicktime',
                'video/x-msvideo', // AVI
                'video/x-matroska', // MKV
                'video/x-m4v',
                'video/hevc',
            ],
            'audios' => [
                'audio/mpeg', // MP3
                'audio/mp4', // M4A
                'audio/x-m4a',
                'audio/amr',
                'audio/3gpp', // 3GP
                'audio/wav',
                'audio/x-wav',
            ],
            'documents' => [
                'application/pdf',
                'application/msword', // DOC
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
                'application/vnd.ms-excel', // XLS
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
                'application/vnd.ms-powerpoint', // PPT
                'application/vnd.openxmlformats-officedocument.presentationml.presentation', // PPTX
                'text/plain',
                'text/csv',
            ],
        ];

        foreach ($mimeTypeMap as $folder => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return $folder;
            }
        }

        return 'other_attachments';
    }

    /**
     * Delete an attachment file if it exists.
     *
     * @param string|null $filePath
     * @param string $disk (default: 'public')
     * @return bool
     */
    public function deleteAttachment($filePath, $disk = 'public')
    {
        if (!$filePath) {
            return false;
        }

        try {
            // Remove '/storage/' prefix if present (for public disk URLs)
            $path = str_replace('/storage/', '', $filePath);

            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                return true;
            }

            return false;
        } catch (Exception $e) {
            // Log if activity logger exists
            if (method_exists($this, 'logActivity')) {
                $this->logActivity(
                    'attachment_delete_failed',
                    "Failed to delete attachment: {$filePath}. Error: {$e->getMessage()}",
                    ['file_path' => $filePath]
                );
            }

            return false;
        }
    }

    /**
     * Get the public URL for an attachment.
     *
     * @param string $filePath
     * @return string
     */
    public function getAttachmentUrl($filePath)
    {
        if (!$filePath) {
            return null;
        }

        // If already a full URL, return as-is
        if (str_starts_with($filePath, 'http')) {
            return $filePath;
        }

        // If stored in public disk, return the storage URL
        return '/storage/' . $filePath;
    }
}

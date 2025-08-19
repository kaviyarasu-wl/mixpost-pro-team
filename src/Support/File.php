<?php

namespace Inovector\Mixpost\Support;

use Illuminate\Http\File as HttpFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class File
{
    public static function fromBase64(string $base64File, ?string $customFilename = null): UploadedFile
    {
        // Get file data base64 string
        $fileData = base64_decode(Arr::last(explode(',', $base64File)));

        // Create temp file and get its absolute path
        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        // Save file data in file
        file_put_contents($tempFilePath, $fileData);

        $tempFileObject = new HttpFile($tempFilePath);

        // Use custom filename if provided, otherwise use the temp filename
        $filename = $customFilename ?: $tempFileObject->getFilename();

        $file = new UploadedFile(
            $tempFileObject->getPathname(),
            $filename,
            $tempFileObject->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        // Close this file after response is sent.
        // Closing the file will cause to remove it from temp director!
        app()->terminating(function () use ($tempFile) {
            fclose($tempFile);
        });

        // return UploadedFile object
        return $file;
    }

    /**
     * Generate a descriptive filename for Unsplash images
     */
    public static function generateUnsplashFilename(array $data, string $extension = 'jpg'): string
    {
        $photographer = self::sanitizeForFilename($data['author'] ?? 'unsplash');
        $description = '';
        $unsplashId = $data['unsplash_id'] ?? 'unknown';

        // Use alt_description first, then description as fallback
        if (!empty($data['alt_description'])) {
            $description = self::sanitizeForFilename($data['alt_description']);
        } elseif (!empty($data['description'])) {
            $description = self::sanitizeForFilename($data['description']);
        } else {
            $description = 'unsplash-photo';
        }

        // Truncate description if too long (keep under 100 chars for the description part)
        if (strlen($description) > 100) {
            $description = substr($description, 0, 100);
            // Try to break at a word boundary
            $lastDash = strrpos($description, '-');
            if ($lastDash !== false && $lastDash > 50) {
                $description = substr($description, 0, $lastDash);
            }
        }

        // Format: photographer-name_descriptive-text_unsplash-id.extension
        return sprintf('%s_%s_%s.%s', $photographer, $description, $unsplashId, $extension);
    }

    /**
     * Generate a descriptive filename for Tenor GIFs
     */
    public static function generateTenorFilename(array $data, string $extension = 'gif'): string
    {
        $searchTerm = '';
        $tenorId = $data['tenor_id'] ?? $data['id'] ?? 'unknown';

        // Use search_term if provided, otherwise use content_description or name
        if (!empty($data['search_term'])) {
            $searchTerm = self::sanitizeForFilename($data['search_term']);
        } elseif (!empty($data['content_description'])) {
            $searchTerm = self::sanitizeForFilename($data['content_description']);
        } elseif (!empty($data['name'])) {
            $searchTerm = self::sanitizeForFilename($data['name']);
        } else {
            $searchTerm = 'animated-gif';
        }

        // Truncate search term if too long (keep under 100 chars)
        if (strlen($searchTerm) > 100) {
            $searchTerm = substr($searchTerm, 0, 100);
            // Try to break at a word boundary
            $lastDash = strrpos($searchTerm, '-');
            if ($lastDash !== false && $lastDash > 50) {
                $searchTerm = substr($searchTerm, 0, $lastDash);
            }
        }

        // Format: tenor_search-term-or-description_tenor-id.gif
        return sprintf('tenor_%s_%s.%s', $searchTerm, $tenorId, $extension);
    }

    /**
     * Sanitize text for use in filenames
     */
    private static function sanitizeForFilename(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Replace spaces and underscores with hyphens
        $text = preg_replace('/[\s_]+/', '-', $text);

        // Remove special characters, keep only alphanumeric, hyphens
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);

        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', $text);

        // Trim hyphens from start and end
        $text = trim($text, '-');

        // Ensure we have something
        return $text ?: 'untitled';
    }
}

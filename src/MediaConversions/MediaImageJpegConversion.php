<?php

namespace Inovector\Mixpost\MediaConversions;

use Inovector\Mixpost\Abstracts\MediaConversion;
use Inovector\Mixpost\Support\MediaConversionData;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Media conversion class to convert images to JPEG format
 * Required for Instagram/Facebook API compliance
 */
class MediaImageJpegConversion extends MediaConversion
{
    protected int $quality = 95; // High quality for social media
    protected ?string $backgroundColor = '#FFFFFF'; // White background for transparent images
    
    /**
     * Get the engine name for this conversion
     */
    public function getEngineName(): string
    {
        return 'ImageJpeg';
    }

    /**
     * Check if this conversion can be performed on the given media
     * Only convert non-JPEG images (PNG, WebP, BMP, etc.)
     */
    public function canPerform(): bool
    {
        // Only process images that are not already JPEG
        if (!$this->isImage()) {
            return false;
        }
        
        // Check if the image is already JPEG
        $mimeType = $this->getMimeType();
        $isJpeg = in_array($mimeType, ['image/jpeg', 'image/jpg']);
        
        // Only convert if it's not already JPEG
        return !$isJpeg;
    }

    /**
     * Get the output path for the converted file
     * Changes extension to .jpg
     */
    public function getPath(): string
    {
        $pathInfo = pathinfo($this->getFilepath());
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        
        // Add suffix and change extension to .jpg
        return $directory . '/' . $filename . '_' . $this->name . '.jpg';
    }

    /**
     * Set the JPEG quality (0-100)
     */
    public function quality(int $quality): static
    {
        $this->quality = max(0, min(100, $quality));
        return $this;
    }

    /**
     * Set the background color for transparent images
     */
    public function backgroundColor(?string $color): static
    {
        $this->backgroundColor = $color;
        return $this;
    }

    /**
     * Handle the actual conversion process
     */
    public function handle(): MediaConversionData|null
    {
        try {
            // Get the original image content
            $content = $this->filesystem($this->getFromDisk())->get($this->getFilepath());

            // Create image instance
            $image = Image::make($content);

            // Handle transparency (PNG with alpha channel)
            if ($this->hasAlphaChannel($image)) {
                // Create a canvas with background color
                $canvas = Image::canvas($image->width(), $image->height(), $this->backgroundColor);
                // Merge the image onto the canvas
                $canvas->insert($image, 'top-left', 0, 0);
                $image = $canvas;
            }

            // Convert to JPEG with specified quality
            $jpegContent = $image->encode('jpg', $this->quality);

            // Save the converted file
            $this->filesystem()->put($this->getPath(), $jpegContent->getEncoded(), 'public');

            // Log the conversion for debugging
            Log::info('Image converted to JPEG', [
                'original_path' => $this->getFilepath(),
                'converted_path' => $this->getPath(),
                'original_mime' => $this->getMimeType(),
                'quality' => $this->quality,
                'size_before' => strlen($content),
                'size_after' => strlen($jpegContent->getEncoded())
            ]);

            return MediaConversionData::conversion($this);
        } catch (\Exception $e) {
            Log::error('Failed to convert image to JPEG', [
                'error' => $e->getMessage(),
                'path' => $this->getFilepath()
            ]);
            
            return null;
        }
    }

    /**
     * Check if the image has an alpha channel (transparency)
     */
    protected function hasAlphaChannel($image): bool
    {
        // Check for PNG or WebP with transparency
        $driver = $image->getDriver();
        
        if ($driver instanceof \Intervention\Image\Gd\Driver) {
            $resource = $image->getCore();
            return imageistruecolor($resource) && imagecolortransparent($resource) == -1;
        }
        
        // For Imagick driver
        if ($driver instanceof \Intervention\Image\Imagick\Driver) {
            $imagick = $image->getCore();
            return $imagick->getImageAlphaChannel() === \Imagick::ALPHACHANNEL_ACTIVATE;
        }
        
        // Default to true for safety (will add white background)
        return true;
    }

    /**
     * Get the MIME type of the original file
     */
    protected function getMimeType(): string
    {
        // Try to get MIME type from file info if available
        $filepath = $this->getFilepath();
        
        // Get from file extension
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
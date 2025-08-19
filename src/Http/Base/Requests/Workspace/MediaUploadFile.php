<?php

namespace Inovector\Mixpost\Http\Base\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Inovector\Mixpost\Events\Media\UploadingMediaFile;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\MediaConversions\MediaImageResizeConversion;
use Inovector\Mixpost\MediaConversions\MediaVideoThumbConversion;
use Inovector\Mixpost\MediaConversions\MediaImageJpegConversion;
use Inovector\Mixpost\Models\Media;
use Inovector\Mixpost\Support\MediaUploader;
use Inovector\Mixpost\Util;

class MediaUploadFile extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', File::types($this->allowedTypes())->max($this->max())]
        ];
    }

    private function max()
    {
        $max = 0;

        if (!$this->file('file')) {
            return $max;
        }

        if ($this->isImage()) {
            $max = config('mixpost.max_file_size.image');
        }

        if ($this->isGif()) {
            $max = config('mixpost.max_file_size.gif');
        }

        if ($this->isVideo()) {
            $max = config('mixpost.max_file_size.video');
        }

        return $max;
    }

    private function isImage(): bool
    {
        return Str::before($this->file('file')->getMimeType(), '/') === 'image';
    }

    private function isGif(): bool
    {
        return Str::after($this->file('file')->getMimeType(), '/') === 'gif';
    }

    private function isVideo(): bool
    {
        return Str::before($this->file('file')->getMimeType(), '/') === 'video';
    }

    private function allowedTypes(): array
    {
        return Util::config('mime_types');
    }

    public function handle(): Media
    {
        UploadingMediaFile::dispatch($this->file('file'));

        $prefix = WorkspaceManager::current()->uuid;
        $date = now()->format('m-Y');

        return MediaUploader::fromFile($this->file('file'))
            ->path("$prefix/uploads/$date")
            ->conversions([
                MediaImageResizeConversion::name('thumb')->width(430),
                MediaVideoThumbConversion::name('thumb')->atSecond(5),
                MediaImageJpegConversion::name('instagram')->quality(95) // Convert to JPEG for Instagram/Facebook
            ])
            ->uploadAndInsert();
    }

    public function messages(): array
    {
        if (!$this->file('file')) {
            return [
                'file.required' => __('mixpost::rules.file_required')
            ];
        }

        $fileType = $this->isImage() ? 'image' : 'video';
        $max = $this->max() / 1024;

        return [
            'file.max' => __('mixpost::rules.file_max_size', ['type' => $fileType, 'max' => $max]),
        ];
    }
}

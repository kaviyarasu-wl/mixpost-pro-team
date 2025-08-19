<?php

namespace Inovector\Mixpost\Http\Base\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inovector\Mixpost\Events\Media\UploadingMediaFile;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Integrations\Unsplash\Jobs\TriggerDownloadJob;
use Inovector\Mixpost\MediaConversions\MediaImageResizeConversion;
use Inovector\Mixpost\MediaConversions\MediaImageJpegConversion;
use Inovector\Mixpost\Support\File;
use Inovector\Mixpost\Support\MediaUploader;
use Inovector\Mixpost\Util;

class MediaDownloadExternal extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['required', 'string', 'in:stock,gifs'],
            'items' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    foreach ($value as $item) {
                        $validKeys = ['id', 'url', 'source', 'author', 'download_data', 'alt_description', 'description', 'unsplash_id', 'name', 'content_description', 'search_term', 'tenor_id'];

                        $extraKeys = array_diff(array_keys($item), $validKeys);

                        if (!empty($extraKeys)) {
                            $fail('The ' . $attribute . ' item contains invalid keys: ' . implode(', ', $extraKeys));
                            break;
                        }

                        foreach (array_flip(Arr::except(array_flip($validKeys), ['source', 'author', 'alt_description', 'description', 'unsplash_id', 'name', 'content_description', 'search_term', 'tenor_id'])) as $key) {
                            if (empty($item[$key])) {
                                $fail('The ' . $attribute . ' item must have a non-empty "' . $key . '" key.');
                                break 2;
                            }
                        }

                        if (!Util::isPublicDomainUrl($item['url'])) {
                            $fail('The ' . $attribute . ' contains non-public domain URLs.');
                        }
                    }
                },
            ],
        ];
    }

    public function handle(): Collection
    {
        return collect($this->input('items'))->map(function ($item) {
            $result = Http::get($item['url']);

            // Generate descriptive filename for Unsplash images
            $customFilename = null;
            if ($this->input('from') === 'stock' && !empty($item['source']) && in_array($item['source'], ['Unsplash', 'GravityWrite'])) {
                $customFilename = File::generateUnsplashFilename([
                    'author' => $item['author'] ?? null,
                    'alt_description' => $item['alt_description'] ?? null,
                    'description' => $item['description'] ?? null,
                    'unsplash_id' => $item['unsplash_id'] ?? $item['id'],
                ]);
            }

            // Generate descriptive filename for Tenor GIFs
            if ($this->input('from') === 'gifs') {
                $customFilename = File::generateTenorFilename([
                    'search_term' => $item['search_term'] ?? null,
                    'content_description' => $item['content_description'] ?? null,
                    'name' => $item['name'] ?? null,
                    'tenor_id' => $item['tenor_id'] ?? $item['id'],
                ]);
            }

            $file = File::fromBase64(base64_encode($result->body()), $customFilename);

            UploadingMediaFile::dispatch($file);

            $prefix = WorkspaceManager::current()->uuid;
            $date = now()->format('m-Y');$media = MediaUploader::fromFile($file)->path("$prefix/uploads/$date")
            ->conversions([
                MediaImageResizeConversion::name('thumb')->width(430),
                MediaImageJpegConversion::name('instagram')->quality(95) // Convert to JPEG for Instagram/Facebook
            ])
                ->data($this->getData($item))
                ->uploadAndInsert();

            $method = 'downloadAction' . Str::studly($this->input('from'));

            $this->$method($item);

            return $media;
        });
    }

    protected function getData(array $item): array
    {
        $data = [];

        if (!empty($item['source'])) {
            $data['source'] = $item['source'];
        }

        if (!empty($item['author'])) {
            $data['author'] = $item['author'];
        }

        // Include additional metadata for Unsplash images
        if (!empty($item['alt_description'])) {
            $data['alt_description'] = $item['alt_description'];
        }

        if (!empty($item['description'])) {
            $data['description'] = $item['description'];
        }

        if (!empty($item['unsplash_id'])) {
            $data['unsplash_id'] = $item['unsplash_id'];
        }

        // Include additional metadata for Tenor GIFs
        if (!empty($item['name'])) {
            $data['name'] = $item['name'];
        }

        if (!empty($item['content_description'])) {
            $data['content_description'] = $item['content_description'];
        }

        if (!empty($item['search_term'])) {
            $data['search_term'] = $item['search_term'];
        }

        if (!empty($item['tenor_id'])) {
            $data['tenor_id'] = $item['tenor_id'];
        }

        return $data;
    }

    protected function downloadActionStock(array $item): void
    {
        if (empty($item['download_data']['download_location'])) {
            return;
        }

        TriggerDownloadJob::dispatch($item['download_data']['download_location']);
    }

    protected function downloadActionGifs(array $item): void
    {
        //
    }
}

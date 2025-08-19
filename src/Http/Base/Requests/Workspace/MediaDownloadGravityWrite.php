<?php

namespace Inovector\Mixpost\Http\Base\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inovector\Mixpost\Models\User;

class MediaDownloadGravityWrite extends FormRequest
{
    private string $source = 'GravityWrite';

    public function authorize(): bool
    {
        if (! $this->user()) {
            Cache::put('external_media_import:' . $this->ip(), $this->input('files'), now()->addMinutes(30));
        }

        return true;
    }

    protected function prepareForValidation()
    {
        // Convert a single file into an array if necessary.
        $files = $this->input('files');
        if ($files && !is_array($files)) {
            $this->merge([
                'files' => [$files],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'files'   => 'required|array|min:1',
            'files.*' => 'required|url',
        ];
    }

    public function handle()
    {
        $workspace = User::find(auth()->id())->workspaces()->first();

        $url = route('mixpost.media.download', ['workspace' => $workspace]);
        $parameters = array_merge($this->setData($this->input('files')), ['_token' => $this->session()->token()]);

        return app()->handle(
            Request::create(
                $url,
                'POST',
                $parameters,
            )
        );
    }

    protected function setData(array $urls): array
    {
        $data = [
            'from' => 'stock',
            'items' => []
        ];

        foreach ($urls as $url) {
            // Extract the path component of the URL
            $path = parse_url($url, PHP_URL_PATH);
            // Get the file name with extension from the path
            $fileWithExt = basename($path);
            // Remove the extension and return just the file name
            $altDescription = pathinfo($fileWithExt, PATHINFO_FILENAME);

            $data['items'][] = [
                'id' => rand(100000, 999999),
                'url' => $url,
                'source' => $this->source,
                'author' => auth()->user()?->name ?? 'Unknown Author',
                'download_data' => [
                    'download_location' => $url
                ],
                'alt_description' => $altDescription ?? 'unknown',
                'description' => "$altDescription from $this->source",
                'unsplash_id' => rand(100000, 999999)
            ];
        }

        return $data;
    }
}

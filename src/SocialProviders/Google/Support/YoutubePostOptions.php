<?php

namespace Inovector\Mixpost\SocialProviders\Google\Support;

use Illuminate\Support\Arr;
use Inovector\Mixpost\Contracts\SocialProviderPostOptions;

class YoutubePostOptions implements SocialProviderPostOptions
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:256'],
            'status' => ['sometimes', 'string', 'in:public,private,unlisted']
        ];
    }

    public function map(array $options = []): array
    {
        return [
            'title' => Arr::get($options, 'title', ''),
            'status' => Arr::get($options, 'status', 'public')
        ];
    }
}

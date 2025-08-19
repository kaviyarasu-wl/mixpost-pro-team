<?php

namespace Inovector\Mixpost\SocialProviders\Pinterest\Support;

use Illuminate\Support\Arr;
use Inovector\Mixpost\Contracts\SocialProviderPostOptions;

class PinterestPostOptions implements SocialProviderPostOptions
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string'],
            'link' => ['sometimes', 'nullable', 'url'],
            'boards' => ['sometimes', 'array'],
            'boards.*' => ['sometimes', 'string', 'nullable']
        ];
    }

    public function map(array $options = []): array
    {
        return [
            'title' => Arr::get($options, 'title', ''),
            'link' => Arr::get($options, 'link', ''),
            'boards' => Arr::get($options, 'boards', ['account-0' => null])
        ];
    }
}

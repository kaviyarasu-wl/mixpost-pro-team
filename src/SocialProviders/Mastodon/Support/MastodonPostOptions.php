<?php

namespace Inovector\Mixpost\SocialProviders\Mastodon\Support;

use Illuminate\Support\Arr;
use Inovector\Mixpost\Contracts\SocialProviderPostOptions;

class MastodonPostOptions implements SocialProviderPostOptions
{
    public function rules(): array
    {
        return [
            'sensitive' => ['sometimes', 'boolean'],
        ];
    }

    public function map(array $options = []): array
    {
        return [
            'sensitive' => (bool)Arr::get($options, 'sensitive', false)
        ];
    }
}

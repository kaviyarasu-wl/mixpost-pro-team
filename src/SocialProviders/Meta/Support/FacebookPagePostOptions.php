<?php

namespace Inovector\Mixpost\SocialProviders\Meta\Support;

use Illuminate\Support\Arr;
use Inovector\Mixpost\Contracts\SocialProviderPostOptions;

class FacebookPagePostOptions implements SocialProviderPostOptions
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:post,reel,story']
        ];
    }

    public function map(array $options = []): array
    {
        return [
            'type' => Arr::get($options, 'type', 'post')
        ];
    }
}

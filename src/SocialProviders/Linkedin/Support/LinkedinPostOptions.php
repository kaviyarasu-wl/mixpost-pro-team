<?php

namespace Inovector\Mixpost\SocialProviders\Linkedin\Support;

use Illuminate\Support\Arr;
use Inovector\Mixpost\Contracts\SocialProviderPostOptions;

class LinkedinPostOptions implements SocialProviderPostOptions
{
    public function rules(): array
    {
        return [
            'visibility' => ['required', 'in:PUBLIC,CONNECTIONS'],
        ];
    }

    public function map(array $options = []): array
    {
        return [
            'visibility' => Arr::get($options, 'visibility', 'PUBLIC'),
        ];
    }
}

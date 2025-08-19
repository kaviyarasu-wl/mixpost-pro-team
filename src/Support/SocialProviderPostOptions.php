<?php

namespace Inovector\Mixpost\Support;

use Inovector\Mixpost\Contracts\SocialProviderPostOptions as SocialProviderPostOptionsContract;

class SocialProviderPostOptions implements SocialProviderPostOptionsContract
{
    public function rules(): array
    {
        return [];
    }

    public function map(array $options = []): array
    {
        return [];
    }
}

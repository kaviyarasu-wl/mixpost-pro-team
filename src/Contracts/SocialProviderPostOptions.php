<?php

namespace Inovector\Mixpost\Contracts;

interface SocialProviderPostOptions
{
    public function rules(): array;

    public function map(array $options = []): array;
}

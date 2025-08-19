<?php

namespace Inovector\Mixpost\SocialProviders\Linkedin;

use Inovector\Mixpost\SocialProviders\Linkedin\Concerns\ManagesPageResources;

class LinkedinPageProvider extends LinkedinProvider
{
    use ManagesPageResources;

    public bool $onlyUserAccount = false;
}

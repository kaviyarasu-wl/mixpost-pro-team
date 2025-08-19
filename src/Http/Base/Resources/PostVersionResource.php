<?php

namespace Inovector\Mixpost\Http\Base\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inovector\Mixpost\Contracts\SocialProvider;
use Inovector\Mixpost\Facades\SocialProviderManager;
use Inovector\Mixpost\Models\Media;
use Inovector\Mixpost\Util;

class PostVersionResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request)
    {
        return [
            'post_id' => $this->post_id,
            'account_id' => $this->account_id,
            'is_original' => $this->is_original,
            'content' => $this->content(),
            'options' => $this->options()
        ];
    }

    protected function isIndexPage(): bool
    {
        return request()->routeIs('mixpost.posts.index');
    }

    protected function isCalendarPage(): bool
    {
        return request()->routeIs('mixpost.calendar');
    }

    protected function content(): Collection
    {
        $items = $this->content_with_relations ?? $this->content;

        return collect($items)->map(function ($item, $index) {
            $data = [
                'body' => (string)$item['body'],
                'media' => Arr::map($item['media'], function ($mediaItem) {
                    if ($mediaItem instanceof Media) {
                        return new MediaResource($mediaItem);
                    }

                    return $mediaItem;
                }),
                'url' => $item['url'] ?? '',
                'opened' => $index === 0,
            ];

            if ($this->isIndexPage()) {
                $data['excerpt'] = Str::limit(Util::removeHtmlTags($item['body']), 150);
            }

            if ($this->isCalendarPage()) {
                $data['excerpt'] = Str::limit(Util::removeHtmlTags($item['body']), 50);
            }

            return $data;
        });
    }

    protected function options(): array
    {
        if (!$this->options) {
            return [];
        }

        $providers = SocialProviderManager::providers();

        return Arr::map($this->options, function ($options, $keyProvider) use ($providers) {
            /** @var SocialProvider $provider */
            $provider = $providers[$keyProvider] ?? null;

            if (!$provider) {
                return [];
            }

            return $provider::postOptions()->map(Arr::wrap($options));
        });
    }
}

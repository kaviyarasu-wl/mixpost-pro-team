<?php

namespace Inovector\Mixpost\SocialProviders\Threads\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Enums\SocialProviderResponseStatus;
use Inovector\Mixpost\Support\SocialProviderResponse;

trait ManagesResources
{
    use SinglePost;
    use CarouselPost;

    public function getAccount(): SocialProviderResponse
    {
        $response = $this->getHttpClient()::withToken($this->getAccessToken()['access_token'])
            ->get("$this->graphUrl/$this->graphVersion/me", [
                'fields' => 'id,username,name,threads_profile_picture_url'
            ]);

        return $this->buildResponse($response, function () use ($response) {
            $data = $response->json();

            return [
                'id' => $data['id'],
                'name' => $data['name'] ?? $data['username'],
                'username' => $data['username'],
                'image' => $data['threads_profile_picture_url'] ?? '',
            ];
        });
    }

    public function publishPost(string $text, Collection $media, array $params = []): SocialProviderResponse
    {
        $data = [
            'text' => $text,
        ];

        if ($lastId = Arr::get($params, 'last_id')) {
            $data['reply_to_id'] = $lastId;
        }

        if ($media->count() === 0 || $media->count() === 1) {
            return $this->createSinglePost($media->first(), $data);
        }

        return $this->createCarouselPost($media, $data);
    }

    public function getAccountMetrics(): SocialProviderResponse
    {
        $response = $this->getHttpClient()::withToken($this->getAccessToken()['access_token'])
            ->get("$this->graphUrl/$this->graphVersion/{$this->values['provider_id']}/threads_insights", [
                'metric' => 'followers_count',
            ]);

        return $this->buildResponse($response, function () use ($response) {
            $data = $response->json();

            return [
                'followers_count' => Arr::get($data, 'data.0.total_value.value', 0),
            ];
        });
    }

    public function deletePost($id): SocialProviderResponse
    {
        return $this->response(SocialProviderResponseStatus::OK, []);
    }
}

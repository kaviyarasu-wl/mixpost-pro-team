<?php

namespace Inovector\Mixpost\SocialProviders\Meta\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inovector\Mixpost\Enums\SocialProviderResponseStatus;
use Inovector\Mixpost\Models\Media;
use Inovector\Mixpost\Support\PostVersionHelpers;
use Inovector\Mixpost\Support\SocialProviderResponse;

trait ManagesInstagramResources
{
    use InstagramComments;
    
    protected array $tempFilesToCleanup = [];

    public function getAccount(): SocialProviderResponse
    {
        return $this->getInstagramAccount($this->values['provider_id']);
    }

    public function getEntities(): SocialProviderResponse
    {
        $response = Http::withToken($this->getAccessToken()['access_token'])
            ->get("$this->apiUrl/$this->apiVersion/me/accounts", [
                'fields' => 'id,name,username,picture{url},instagram_business_account',
                'limit' => 200
            ]);

        return $this->buildResponse($response, function () use ($response) {
            return $response->collect('data')->filter(function ($item) {
                return isset($item['instagram_business_account']);
            })->map(function ($item) {
                return $this->getInstagramAccount($item['instagram_business_account']['id'])->context();
            })->toArray();
        });
    }

    public function getInstagramAccount($id): SocialProviderResponse
    {
        $response = Http::get("$this->apiUrl/$this->apiVersion/$id", [
            'fields' => 'id,name,username,profile_picture_url',
            'access_token' => $this->getAccessToken()['access_token']
        ]);

        return $this->buildResponse($response, function () use ($response) {
            $data = $response->json();

            return [
                'id' => $data['id'],
                'name' => $data['username'] ?? '',
                'username' => $data['username'] ?? '',
                'image' => $data['profile_picture_url'] ?? '',
            ];
        });
    }

    /**
     * Copy image to local server to avoid Instagram API fetch issues with external domains
     */
    private function getLocalImageUrl(string $imageUrl): string
    {
        try {
            // Check if already a local URL
            if (str_contains($imageUrl, request()->getHost())) {
                return $imageUrl;
            }
            
            // Generate unique filename to avoid conflicts
            $filename = uniqid('instagram_') . '_' . basename($imageUrl);
            $tempPath = 'instagram-temp/' . $filename;
            
            // Download image from external source
            $imageContent = @file_get_contents($imageUrl);
            if ($imageContent === false) {
                return $imageUrl; // Fallback to original URL
            }
            
            // Save to local public storage
            Storage::disk('public')->put($tempPath, $imageContent);
            
            // Track file for cleanup
            $this->tempFilesToCleanup[] = $tempPath;
            
            // Generate local URL
            $localUrl = asset('storage/' . $tempPath);
            
            return $localUrl;
        } catch (\Exception $e) {
            return $imageUrl; // Fallback to original URL
        }
    }
    
    /**
     * Copy video to local server to avoid Instagram API fetch issues with external domains
     */
    private function getLocalVideoUrl(string $videoUrl): string
    {
        try {
            // Check if already a local URL
            if (str_contains($videoUrl, request()->getHost())) {
                return $videoUrl;
            }
            
            // Generate unique filename to avoid conflicts
            $filename = uniqid('instagram_video_') . '_' . basename($videoUrl);
            $tempPath = 'instagram-temp/' . $filename;
            
            // Download video from external source
            $videoContent = @file_get_contents($videoUrl);
            if ($videoContent === false) {
                return $videoUrl; // Fallback to original URL
            }
            
            // Save to local public storage
            Storage::disk('public')->put($tempPath, $videoContent);
            
            // Track file for cleanup
            $this->tempFilesToCleanup[] = $tempPath;
            
            // Generate local URL
            $localUrl = asset('storage/' . $tempPath);
            
            return $localUrl;
        } catch (\Exception $e) {
            return $videoUrl; // Fallback to original URL
        }
    }
    
    /**
     * Clean up temporary files created for Instagram
     */
    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFilesToCleanup as $tempPath) {
            try {
                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->delete($tempPath);
                }
            } catch (\Exception $e) {
                // Silently ignore cleanup errors
            }
        }
        
        // Reset the array
        $this->tempFilesToCleanup = [];
    }

    public function publishPost(string $text, Collection $media, array $params = []): SocialProviderResponse
    {
        if (!$media->count()) {
            return $this->response(SocialProviderResponseStatus::ERROR, ['no_media_selected']);
        }

        $response = null;
        $isReel = Arr::get($params, 'type') === 'reel';
        $isStory = Arr::get($params, 'type') === 'story';

        if ($isReel && $media->count() === 1) {
            if (isset($params['video_thumbs']) && is_array($params['video_thumbs'])) {
                $thumb = PostVersionHelpers::getThumbForMediaId($media->first()->id, $params['video_thumbs']);
            }
            $response = $this->publishInstagramReel($text, $media->first(), $thumb ?? null);
        }

        if ($isReel && $media->count() > 1) {
            $response = $this->response(SocialProviderResponseStatus::ERROR, ['reel_supports_one_video']);
        }

        if ($isStory && $media->count() === 1) {
            $response = $this->publishStory($media->first());

            if ($response->hasError()) {
                // Clean up temp files on error
                $this->cleanupTempFiles();
                return $response;
            }

            // Clean up temp files on success
            $this->cleanupTempFiles();
            
            return $response->useContext([
                'id' => $response->id,
                'data' => [
                    'story' => true
                ]
            ]);
        }

        if ($isStory && $media->count() > 1) {
            $response = $this->response(SocialProviderResponseStatus::ERROR, ['story_single_media_limit']);
        }

        if (!$isReel && !$isStory && $media->count() === 1) {
            $response = $this->publishSingleMediaPost($text, $media->first());
        }

        if (!$isReel && !$isStory && $media->count() > 1) {
            $response = $this->publishCarouselPost($text, $media);
        }

        if ($response && $response->hasError()) {
            // Clean up temp files on error
            $this->cleanupTempFiles();
            return $response;
        }

        // If we have a response and if it is successful,
        // we need to extract the shortcode of the post and
        // attach it to the response.
        $postResponse = $this->getPost($response->id, 'shortcode');

        $data = [];

        if ($postResponse->shortcode) {
            $data['shortcode'] = $postResponse->shortcode;
        }

        // Clean up temp files on success
        $this->cleanupTempFiles();

        return $response->useContext([
            'id' => $response->id,
            'data' => $data
        ]);
    }

    public function publishSingleMediaPost(string $text, Media $mediaItem): SocialProviderResponse
    {
        $data = [
            'access_token' => $this->getAccessToken()['access_token'],
            'caption' => $text,
            'alt_text' => $mediaItem->alt_text,
        ];

        if ($mediaItem->isVideo()) {
            $data['media_type'] = 'VIDEO';
            // Copy to local server to avoid Instagram API fetch issues
            $localUrl = $this->getLocalVideoUrl($mediaItem->getUrl());
            $data['video_url'] = $localUrl;
        }

        if ($mediaItem->isImage()) {
            // Use JPEG conversion for Instagram if available, otherwise fall back to original
            $instagramConversion = $mediaItem->getConversionUrl('instagram');
            $imageUrl = $instagramConversion ?: $mediaItem->getUrl();
            
            // Copy to local server to avoid Instagram API fetch issues
            $localUrl = $this->getLocalImageUrl($imageUrl);
            $data['image_url'] = $localUrl;
        }

        $response = $this->buildResponse(
            $this->getHttpClient()::timeout($mediaItem->isImage() ? 30 : 100)
                ->post("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/media", $data)
        );

        if ($response->hasError()) {
            return $response;
        }

        return $this->publishContainer($response->id);
    }

    public function publishInstagramReel(string $text, Media $mediaItem, ?Media $thumb = null): SocialProviderResponse
    {
        if (!$mediaItem->isVideo()) {
            return $this->response(SocialProviderResponseStatus::ERROR, ['reel_only_video_allowed']);
        }

        $data = [
            'access_token' => $this->getAccessToken()['access_token'],
            'caption' => $text,
            'media_type' => 'REELS',
            'video_url' => $this->getLocalVideoUrl($mediaItem->getUrl()),
            'alt_text' => $mediaItem->alt_text,
        ];

        if($thumb){
            // Also copy thumbnail to local storage
            $localThumbUrl = $this->getLocalImageUrl($thumb->getUrl());
            $data['cover_url'] = $localThumbUrl;
        }

        $response = $this->buildResponse(
            $this->getHttpClient()::post("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/media", $data)
        );

        if ($response->hasError()) {
            return $response;
        }

        return $this->publishContainer($response->id);
    }

    public function publishCarouselPost(string $text, Collection $media): array|SocialProviderResponse
    {
        $mediaContainerIds = [];

        foreach ($media as $item) {
            // Use JPEG conversion for Instagram if available, otherwise fall back to original
            $instagramConversion = $item->getConversionUrl('instagram');
            $imageUrl = $instagramConversion ?: $item->getUrl();
            
            // Copy to local server to avoid Instagram API fetch issues
            $localUrl = $this->getLocalImageUrl($imageUrl);
            
            $mediaContainerResponse = $this->buildResponse(Http::post("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/media", [
                'access_token' => $this->getAccessToken()['access_token'],
                'is_carousel_item' => true,
                'image_url' => $localUrl,
                'alt_text' => $item->alt_text,
            ]));

            if ($mediaContainerResponse->hasError()) {
                return $mediaContainerResponse;
            }

            $mediaContainerIds[] = $mediaContainerResponse->id;
        }

        $carouselContainer = $this->buildResponse(Http::post("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/media", [
            'access_token' => $this->getAccessToken()['access_token'],
            'media_type' => 'CAROUSEL',
            'children' => $mediaContainerIds,
            'caption' => $text
        ]));

        if ($carouselContainer->hasError()) {
            return $carouselContainer;
        }

        return $this->publishContainer($carouselContainer->id);
    }

    public function publishStory(Media $mediaItem): SocialProviderResponse
    {
        $data = [
            'access_token' => $this->getAccessToken()['access_token'],
            'media_type' => 'STORIES',
        ];

        if ($mediaItem->isVideo()) {
            // Copy to local server to avoid Instagram API fetch issues
            $localUrl = $this->getLocalVideoUrl($mediaItem->getUrl());
            $data['video_url'] = $localUrl;
        }

        if ($mediaItem->isImage()) {
            // Use JPEG conversion for Instagram if available, otherwise fall back to original
            $instagramConversion = $mediaItem->getConversionUrl('instagram');
            $imageUrl = $instagramConversion ?: $mediaItem->getUrl();
            
            // Copy to local server to avoid Instagram API fetch issues
            $localUrl = $this->getLocalImageUrl($imageUrl);
            $data['image_url'] = $localUrl;
        }

        $response = $this->buildResponse(
            $this->getHttpClient()::timeout($mediaItem->isImage() ? 30 : 100)
                ->post("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/media", $data)
        );

        if ($response->hasError()) {
            return $response;
        }

        return $this->publishContainer($response->id);
    }

    public function publishContainer(string|int $itemContainerId): array|SocialProviderResponse
    {
        $responseContainer = null;

        do {
            $responseContainer = $this->getContainer($itemContainerId);

            $inProgress = $responseContainer->status_code === 'IN_PROGRESS';

            // If it is in progress, we will wait 1 minute until the next check.
            if ($inProgress) {
                // TODO: sleep seconds depend by file size
                sleep(60);
            }
        } while ($inProgress === true);

        if (!$responseContainer->status_code) {
            return $this->response(SocialProviderResponseStatus::ERROR, $responseContainer->context());
        }

        // Check specific endpoint status
        if ($responseContainer->status_code === 'ERROR') {
            return $this->response(SocialProviderResponseStatus::ERROR, [$responseContainer->status]);
        }

        if ($responseContainer->status_code === 'EXPIRED') {
            return $this->response(SocialProviderResponseStatus::ERROR, ['session_expired']);
        }

        if ($responseContainer->status_code === 'PUBLISHED') {
            return $this->response(SocialProviderResponseStatus::ERROR, ['media_already_published']);
        }

        $response = $this->getHttpClient()::withToken($this->getAccessToken()['access_token'])
            ->post("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/media_publish", [
                'creation_id' => $itemContainerId
            ]);

        return $this->buildResponse($response);
    }

    public function getContainer($containerId): SocialProviderResponse
    {
        $response = Http::get("$this->apiUrl/$this->apiVersion/$containerId", [
            'access_token' => $this->getAccessToken()['access_token'],
            'fields' => 'status,status_code'
        ]);

        return $this->buildResponse($response);
    }

    public function getContentPublishLimit(): SocialProviderResponse
    {
        $response = Http::get("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/content_publishing_limit", [
            'access_token' => $this->getAccessToken()['access_token']
        ]);

        return $this->buildResponse($response);
    }

    public function getAccountMetrics(): SocialProviderResponse
    {
        $response = Http::get("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}", [
            'fields' => 'followers_count,follows_count,media_count',
            'access_token' => $this->getAccessToken()['access_token']
        ]);

        return $this->buildResponse($response);
    }

    public function getInsightsTimeSeries(): SocialProviderResponse
    {
        $data = [
            'access_token' => $this->getAccessToken()['access_token'],
            'metric' => 'follower_count,reach',
            'period' => 'day',
            'since' => Carbon::now('UTC')->subDays(30)->toDateString(),
            'until' => Carbon::today('UTC')->toDateString(),
        ];

        $response = Http::get("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/insights", $data);

        return $this->buildResponse($response);
    }

    public function getInsightsTotalValue(): SocialProviderResponse
    {
        $data = [
            'access_token' => $this->getAccessToken()['access_token'],
            'metric' => 'total_interactions,accounts_engaged,likes,comments,shares,replies,views,profile_links_taps',
            'period' => 'day',
            'metric_type' => 'total_value',
            'since' => Carbon::now('UTC')->subDays(30)->toDateString(),
            'until' => Carbon::today('UTC')->toDateString(),
        ];

        $response = Http::get("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/insights", $data);

        return $this->buildResponse($response);
    }

    public function getPost(string $mediaId, string $fields = 'id,ig_id'): SocialProviderResponse
    {
        $response = $this->getHttpClient()::withToken($this->getAccessToken()['access_token'])
            ->get("$this->apiUrl/$this->apiVersion/$mediaId", [
                'fields' => $fields
            ]);

        return $this->buildResponse($response);
    }

    public function getMedia(string $paginationAfter = ''): SocialProviderResponse
    {
        $data = [
            'access_token' => $this->getAccessToken()['access_token'],
            'since' => Carbon::now('UTC')->subYear()->toDateString(),
            'until' => Carbon::today('UTC')->toDateString(),
            'limit' => 100,
            'fields' => 'id,caption,comments_count,is_comment_enabled,is_shared_to_feed,like_count,media_product_type,media_type,media_url,permalink,shortcode,thumbnail_url,timestamp,username'
        ];

        if ($paginationAfter) {
            $data['after'] = $paginationAfter;
        }

        $response = Http::get("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/media", $data);

        return $this->buildResponse($response);
    }

    public function getStories(): SocialProviderResponse
    {
        $response = Http::get("$this->apiUrl/$this->apiVersion/{$this->values['provider_id']}/stories", [
            'access_token' => $this->getAccessToken()['access_token'],
        ]);

        return $this->buildResponse($response);
    }
}

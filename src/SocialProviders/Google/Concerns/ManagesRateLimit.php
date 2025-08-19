<?php

namespace Inovector\Mixpost\SocialProviders\Google\Concerns;

use Closure;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Inovector\Mixpost\Enums\SocialProviderResponseStatus;
use Inovector\Mixpost\Support\SocialProviderResponse;

trait ManagesRateLimit
{
    /**
     * YouTube Data API (v3) - Quota Calculator
     *
     * @see https://developers.google.com/youtube/v3/determine_quota_cost
     */
    public function getQuotaUsage(array $headers): array|null
    {
        return null;
    }

    /**
     * @param $response Response
     */
    public function buildResponse($response, Closure $okResult = null): SocialProviderResponse
    {
        if (in_array($response->status(), [200, 201])) {
            return $this->response(SocialProviderResponseStatus::OK, $okResult ? $okResult() : $response->json());
        }

        if ($response->status() === 401) {
            return $this->response(SocialProviderResponseStatus::UNAUTHORIZED, $response->json());
        }

        if ($response->status() === 403) {
            $now = Carbon::now('UTC');
            $nextMidnight = Carbon::tomorrow('UTC');
            $retryAfter = (int)$now->diffInSeconds($nextMidnight);

            return $this->response(
                SocialProviderResponseStatus::EXCEEDED_RATE_LIMIT,
                $this->rateLimitExceedContext($retryAfter, 'The daily quota has been exceeded.'),
                false,
                $retryAfter,
                true
            );
        }

        return $this->response(SocialProviderResponseStatus::ERROR, $response->json());
    }
}

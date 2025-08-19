<?php

namespace Inovector\Mixpost\AIProviders\Claude;

use Illuminate\Support\Arr;
use Inovector\Mixpost\Abstracts\AIProvider;
use Inovector\Mixpost\Data\AIChoiceData;
use Inovector\Mixpost\Enums\AIProviderResponseStatus;
use Inovector\Mixpost\Responses\AIProviderResponse;
use Inovector\Mixpost\Services\ClaudeService;
use Anthropic\Anthropic;
use Anthropic\Client as AnthropicClient;

class ClaudeProvider extends AIProvider
{
    public static function name(): string
    {
        return 'claude';
    }

    public static function nameLocalized(): string
    {
        return 'Claude';
    }

    public static function service(): string
    {
        return ClaudeService::class;
    }

    public function generateText(string $prompt, string $instructions = ''): AIProviderResponse
    {
        // Get the selected model from configuration, fallback to default if not set
        $model = $this->getServiceConfiguration('model') ?? 'claude-3-5-sonnet-20241022';

        $result = $this->client()->chat()->create([
            'model' => $model,
            'max_tokens' => 8192,
            'temperature' => 0,
            'system' => $instructions,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ],
                    ]
                ]
            ],
        ]);

        return AIProviderResponse::withStatus(AIProviderResponseStatus::OK)
            ->withChoices(Arr::map($result->choices, function ($choice) {
                return AIChoiceData::from(index: $choice->index, messageContent: $choice->message->content);
            }));
    }

    public function generateImage(): AIProviderResponse
    {
        // TODO: Implement generateImage() method.
    }

    protected function client(): AnthropicClient
    {
        $headers = [
            'anthropic-version' => '2023-06-01',
            'anthropic-beta' => 'messages-2023-12-15',
            'content-type' => 'application/json',
            'x-api-key' => $this->getServiceConfiguration('secret_key')
        ];

        return Anthropic::factory()
            ->withHeaders($headers)
            ->make();
    }
}

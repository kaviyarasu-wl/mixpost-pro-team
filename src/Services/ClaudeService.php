<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;
use Inovector\Mixpost\Enums\ServiceGroup;

class ClaudeService extends Service
{
    public static function group(): ServiceGroup
    {
        return ServiceGroup::AI;
    }

    public static array $exposedFormAttributes = ['model'];

    public static function name(): string
    {
        return 'claude';
    }

    public static function form(): array
    {
        return [
            'secret_key' => '',
            'model' => 'claude-3-5-sonnet-20241022', // Default to Claude 3.5 Sonnet
        ];
    }

    public static function formRules(): array
    {
        return [
            "secret_key" => ['required'],
            "model" => ['required', 'string', 'in:claude-opus-4-20250514,claude-sonnet-4-20250514,claude-3-7-sonnet-20250219,claude-3-5-haiku-20241022,claude-3-5-sonnet-20241022,claude-3-opus-20240229,claude-3-sonnet-20240229,claude-3-haiku-20240307']
        ];
    }

    public static function formMessages(): array
    {
        return [
            'secret_key.required' => __('validation.required', ['attribute' => 'API Key']),
            'model.required' => __('validation.required', ['attribute' => 'Model']),
            'model.in' => __('validation.in', ['attribute' => 'Model']),
        ];
    }

    /**
     * Get available Claude models with their descriptions
     *
     * @return array
     */
    public static function availableModels(): array
    {
        return [
            'claude-opus-4-20250514' => [
                'name' => 'Claude 4 Opus',
                'description' => 'Most capable model with highest level of intelligence and capability',
                'badge' => 'Latest',
                'available' => true,
            ],
            'claude-sonnet-4-20250514' => [
                'name' => 'Claude 4 Sonnet',
                'description' => 'High-performance model with balanced intelligence and performance',
                'badge' => 'Latest',
                'available' => true,
            ],
            'claude-3-7-sonnet-20250219' => [
                'name' => 'Claude 3.7 Sonnet',
                'description' => 'High performance with extended thinking capabilities',
                'badge' => 'Extended Thinking',
                'available' => true,
            ],
            'claude-3-5-haiku-20241022' => [
                'name' => 'Claude 3.5 Haiku',
                'description' => 'Fastest model with enhanced capabilities',
                'badge' => 'Fast',
                'available' => true,
            ],
            'claude-3-5-sonnet-20241022' => [
                'name' => 'Claude 3.5 Sonnet',
                'description' => 'Great balance of performance and cost',
                'badge' => 'Recommended',
                'available' => true,
            ],
            'claude-3-opus-20240229' => [
                'name' => 'Claude 3 Opus',
                'description' => 'Previous generation - powerful model for complex tasks',
                'badge' => 'Legacy',
                'available' => true,
            ],
            'claude-3-sonnet-20240229' => [
                'name' => 'Claude 3 Sonnet',
                'description' => 'Previous generation - balanced performance',
                'badge' => 'Legacy',
                'available' => true,
            ],
            'claude-3-haiku-20240307' => [
                'name' => 'Claude 3 Haiku',
                'description' => 'Previous generation - fastest model',
                'badge' => 'Legacy',
                'available' => true,
            ],
        ];
    }
}

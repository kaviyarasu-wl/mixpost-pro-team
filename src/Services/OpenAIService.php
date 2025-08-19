<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;
use Inovector\Mixpost\Enums\ServiceGroup;
use Illuminate\Validation\Rule;

class OpenAIService extends Service
{
    public static function group(): ServiceGroup
    {
        return ServiceGroup::AI;
    }

    public static array $exposedFormAttributes = ['model'];

    public static function name(): string
    {
        return 'openai';
    }

    public static function form(): array
    {
        return [
            'secret_key' => '',
            'model' => 'gpt-4o-mini',
        ];
    }

    public static function formRules(): array
    {
        return [
            'secret_key' => ['required'],
            'model' => [
                'required',
                Rule::in([
                    'gpt-4-turbo',
                    'gpt-4-turbo-mini',
                    'gpt-4',
                    'gpt-3.5-turbo',
                    'gpt-4-vision-preview',
                    'gpt-4-turbo-vision',
                    'gpt-4o-mini',
                ])
            ]
        ];
    }

    public static function formMessages(): array
    {
        return [
            'secret_key' => __('validation.required', ['attribute' => 'API Key']),
            'model.required' => __('validation.required', ['attribute' => 'Model']),
            'model.in' => __('validation.in', ['attribute' => 'Model']),
        ];
    }
}

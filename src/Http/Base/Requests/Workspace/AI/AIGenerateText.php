<?php

namespace Inovector\Mixpost\Http\Base\Requests\Workspace\AI;

use App\Helpers\UsageTracker;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Inovector\Mixpost\Configs\AIConfig;
use Inovector\Mixpost\Events\AI\AITextGenerated;
use Inovector\Mixpost\Facades\AIManager;
use Inovector\Mixpost\Models\User;
use Inovector\Mixpost\Responses\AIProviderResponse;

class AIGenerateText extends FormRequest
{
    public AIProviderResponse $response;

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:1000'],
            'tone' => ['required', Rule::in(['neutral', 'friendly', 'formal', 'edgy', 'engaging'])],
            'character_limit' => ['required', 'integer', 'min:1']
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = User::find(auth()->id());
            try {
                (new UsageTracker())->valid(['credit_ai_mode' => 'Good'], $user);
            } catch (\Exception $e) {
                $validator->errors()->add('subscription', $e->getMessage());
                return;
            }

            $this->generateResponse();

            if ($this->response->hasError()) {
                $validator->errors()->add('ai_error', $this->response->context);
            }
        });
    }

    public function handle(): ?string
    {
        return $this->response->firstChoice()?->messageContent;
    }

    private function generateResponse(): void
    {
        $user = User::find(auth()->id());
        $language = $user->language?->language ?? 'English';
        $agentInstructions = app(AIConfig::class)->get('instructions');
        $characterLimit = "Ensure that the reply must not exceed the limit of {$this->input('character_limit')} characters.";
        $languageOutputInstructions = "Generate response in explicitly requested language, otherwise use {$language}.";

        $this->response = AIManager::connect()->generateText(
            prompt: strip_tags($this->input('prompt')),
            instructions: implode("\n\n", [$agentInstructions, $characterLimit, $languageOutputInstructions])
        );

        AITextGenerated::dispatch($this->response);
    }
}

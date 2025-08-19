<?php

namespace Inovector\Mixpost\Http\Base\Requests\Workspace\AI;

use App\Helpers\UsageTracker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inovector\Mixpost\Configs\AIConfig;
use Inovector\Mixpost\Events\AI\AITextGenerated;
use Inovector\Mixpost\Facades\AIManager;
use Inovector\Mixpost\Models\User;
use Inovector\Mixpost\Responses\AIProviderResponse;

class AIModifyText extends FormRequest
{
    public AIProviderResponse $response;

    public function rules(): array
    {
        return [
            'text' => ['required', 'string'],
            'character_limit' => ['required', 'integer', 'min:1'],
            'command' => ['required', 'string', Rule::in(array_keys($this->commands()))],
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

    public function handle(): string
    {
        return $this->response->firstChoice()?->messageContent;
    }

    private function generateResponse(): void
    {
        $user = User::find(auth()->id());
        $language = $user->language?->language ?? 'English';
        $agentInstructions = app(AIConfig::class)->get('instructions');
        $commandInstructions = $this->getCommandSpecificInstruction($this->input('command'));
        $characterLimit = "Ensure that the reply should be within {$this->input('character_limit')} characters.";
        $languageOutputInstructions = "Generate response in explicitly requested language, otherwise use {$language}.";

        $this->response = AIManager::connect()->generateText(
            prompt: strip_tags($this->input('text')),
            instructions: implode("\n\n", [$agentInstructions, $commandInstructions, $characterLimit, $languageOutputInstructions])
        );

        AITextGenerated::dispatch($this->response);
    }

    private function commands(): array
    {
        return [
            'rephrase' => 'Please rephrase the following response.',
            'fix_spelling_grammar' => 'Please fix the spelling and grammar of the following response.',
            'expand' => 'Please expand the following response.',
            'shorten' => 'Please shorten the following response.',
            'simplify' => 'Please simplify the following response.',
            'friendly_tone' => 'Please make the following response more friendly.',
            'formal_tone' => 'Please make the following response more formal.',
            'edgy_tone' => 'Please make the following response more edgy.',
            'engaging_tone' => 'Please make the following response more engaging.',
        ];
    }

    private function getCommandSpecificInstruction(string $command): string
    {
        return Arr::get($this->commands(), $command, '');
    }
}

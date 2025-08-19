<?php

namespace Inovector\Mixpost;

use Illuminate\Support\Arr;
use Inovector\Mixpost\Configs\ThemeConfig;

class Theme
{
    public array $customColors = [];

    public function __construct(public readonly ThemeConfig $config) {}

    public function config(): ThemeConfig
    {
        return $this->config;
    }

    public function setCustomColors($value): void
    {
        $this->customColors = $value;
    }

    public function colors(): array
    {
        if (!empty($this->customColors)) {
            return $this->customColors;
        }

        return [
            'primary_colors' => [
                '50' => "#ebf4ff",
                '100' => "#dbe9ff",
                '200' => "#bed7ff",
                '300' => "#97bbff",
                '400' => "#6e92ff",
                '500' => "#2E41FF",
                '600' => "#2e42ff",
                '700' => "#202fe2",
                '800' => "#1d2bb6",
                '900' => "#202d8f",
                '950' => "#131953",
            ],
            'primary_ring_focus' => 'rgba(184,180,228,0.5)',
            'primary_context' => '#ffffff',
            'alert' => '#1F1B4B',
            'alert_context' => '#e5e7eb',
        ];
    }

    public function primaryColor(string $weight = '500'): string
    {
        return Arr::get($this->colors(), "primary_colors.$weight");
    }

    public function render(): string
    {
        return view('mixpost::partial.theme', $this->colors())->render();
    }
}

<?php

namespace Inovector\Mixpost\Http\Base\Requests\Main;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;
use Closure;

class ExtractUrlMeta extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => ['required', 'url']
        ];
    }

    public function handle(): array
    {
        $data = [
            'title' => '',
            'description' => '',
            'image' => ''
        ];

        try {
            $response = Http::timeout(15)->get($this->get('url'));

            $doc = new DOMDocument();
            @$doc->loadHTML($response->body());

            $xpath = new DOMXPath($doc);

            $data['title'] = $this->getAttributeContent($xpath, 'property', 'og:title', function () use ($xpath) {
                return $xpath->query('//title')->item(0)?->nodeValue;
            });
            $data['description'] = $this->getAttributeContent($xpath, 'property', 'og:description', '//meta[@name="description"]');
            $data['image'] = $this->getAttributeContent($xpath, 'property', 'og:image', '//img');

            $twitterData = [
                'title' => $this->getAttributeContent($xpath, 'name', 'twitter:title'),
                'description' => $this->getAttributeContent($xpath, 'name', 'twitter:description'),
                'image' => $this->getAttributeContent($xpath, 'name', 'twitter:image'),
            ];

        } catch (ConnectionException $e) {
            $twitterData = $data;
        }

        return [
            'default' => $data,
            'twitter' => $twitterData
        ];
    }

    private function getAttributeContent($xpath, $attribute, $attributeValue, Closure|string $fallbackQuery = '')
    {
        $node = $xpath->query('//meta[@' . $attribute . '="' . $attributeValue . '"]')->item(0);

        if ($node) {
            return $node->getAttribute('content');
        } elseif ($fallbackQuery) {
            if (is_callable($fallbackQuery)) {
                return $fallbackQuery();
            }

            $fallbackNode = $xpath->query($fallbackQuery)->item(0);
            return $fallbackNode ? $fallbackNode->getAttribute('content') : '';
        }

        return '';
    }
}

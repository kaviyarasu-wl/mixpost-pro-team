<?php

namespace Inovector\Mixpost\MediaConversions;

use Inovector\Mixpost\Abstracts\MediaConversion;
use Inovector\Mixpost\Support\ImageResizer;
use Inovector\Mixpost\Support\MediaConversionData;

class MediaImageResizerConversion extends MediaConversion
{
    protected ?float $width;

    protected ?float $height = null;

    public function getEngineName(): string
    {
        return 'ImageResizer';
    }

    public function canPerform(): bool
    {
        return $this->isImage() && ! $this->isGifImage();
    }

    public function getPath(): string
    {
        return $this->getFilePathWithSuffix();
    }

    public function width(?float $value = null): static
    {
        $this->width = $value;

        return $this;
    }

    public function height(?float $value = null): static
    {
        $this->height = $value;

        return $this;
    }

    public function handle(): ?MediaConversionData
    {
        $content = $this->filesystem($this->getFromDisk())->get($this->getFilepath());

        ImageResizer::make($content)
            ->disk($this->getToDisk())
            ->path($this->getPath())
            ->resize($this->width, $this->height);

        return MediaConversionData::conversion($this);
    }
}

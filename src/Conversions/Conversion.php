<?php

namespace Jegex\Media\Conversions;

use Spatie\Image\Enums\Fit;

class Conversion
{
    protected string $name;

    protected array $manipulations = [];

    protected bool $shouldOptimize = false;

    protected bool $performOnQueue = true;

    protected ?string $format = null;

    protected string $extractVideoFrameAtSecond = '00:00:10';

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function width(int $width): self
    {
        $this->manipulations['width'] = [$width];

        return $this;
    }

    public function height(int $height): self
    {
        $this->manipulations['height'] = [$height];

        return $this;
    }

    public function fit(Fit $fit): self
    {
        $this->manipulations['fit'] = [$fit->value];

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function quality(int $quality): self
    {
        $this->manipulations['quality'] = [$quality];

        return $this;
    }

    public function brightness(int $brightness): self
    {
        $this->manipulations['brightness'] = [$brightness];

        return $this;
    }

    public function contrast(float $contrast): self
    {
        $this->manipulations['contrast'] = [$contrast];

        return $this;
    }

    public function blur(int $blur): self
    {
        $this->manipulations['blur'] = [$blur];

        return $this;
    }

    public function gamma(float $gamma): self
    {
        $this->manipulations['gamma'] = [$gamma];

        return $this;
    }

    public function flip(string $flip): self
    {
        $this->manipulations['flip'] = [$flip];

        return $this;
    }

    public function optimize(): self
    {
        $this->shouldOptimize = true;

        return $this;
    }

    public function shouldOptimize(): bool
    {
        return $this->shouldOptimize;
    }

    public function nonQueued(): self
    {
        $this->performOnQueue = false;

        return $this;
    }

    public function queued(): self
    {
        $this->performOnQueue = true;

        return $this;
    }

    public function shouldBeQueued(): bool
    {
        return $this->performOnQueue;
    }

    public function manipulate(string $name, ...$parameters): self
    {
        $this->manipulations[$name] = $parameters;

        return $this;
    }

    public function getManipulations(): array
    {
        return $this->manipulations;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'manipulations' => $this->manipulations,
            'optimize' => $this->shouldOptimize,
            'queued' => $this->performOnQueue,
            'format' => $this->format,
        ];
    }
}

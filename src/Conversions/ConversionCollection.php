<?php

namespace Jegex\Media\Conversions;

use Illuminate\Support\Collection;

class ConversionCollection extends Collection
{
    public static function createForMedia($media, array $modelConversions = []): self
    {
        if (! empty($modelConversions)) {
            $conversions = collect($modelConversions)
                ->map(fn (Conversion $conversion) => $conversion->toArray())
                ->values()
                ->toArray();

            return new self($conversions);
        }

        $conversions = config('media.conversions', []);

        return new self($conversions);
    }

    public function getByName(string $name): ?array
    {
        return $this->first(function ($conversion) use ($name) {
            if (is_array($conversion)) {
                return ($conversion['name'] ?? '') === $name;
            }

            return $conversion->getName() === $name;
        });
    }

    public function getQueuedConversions(): self
    {
        return $this->filter(function ($conversion) {
            if (is_array($conversion)) {
                return $conversion['queued'] ?? config('media.queue_conversions_by_default', true);
            }

            return $conversion->shouldBeQueued();
        })->values();
    }

    public function getNonQueuedConversions(): self
    {
        return $this->filter(function ($conversion) {
            if (is_array($conversion)) {
                return ! ($conversion['queued'] ?? config('media.queue_conversions_by_default', true));
            }

            return ! $conversion->shouldBeQueued();
        })->values();
    }

    public function shouldRunAfterCommit(): bool
    {
        return config('media.queue_conversions_after_database_commit', true);
    }
}

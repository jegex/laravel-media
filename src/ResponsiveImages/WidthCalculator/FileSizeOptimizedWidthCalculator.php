<?php

namespace Jegex\Media\ResponsiveImages\WidthCalculator;

class FileSizeOptimizedWidthCalculator
{
    protected float $sizeRatio = 0.7;

    protected int $minimumWidth = 320;

    protected int $targetWidths = 10;

    public function calculateWidths(int $fileSize, int $width, int $height): array
    {
        $targetWidths = [$this->minimumWidth];
        $previousWidth = $this->minimumWidth;

        for ($i = 0; $i < $this->targetWidths; $i++) {
            $newWidth = round($previousWidth / $this->sizeRatio);

            if ($newWidth > $width) {
                $newWidth = $width;
            }

            if ($newWidth <= $previousWidth) {
                break;
            }

            $targetWidths[] = $newWidth;
            $previousWidth = $newWidth;
        }

        $targetWidths[] = $width;

        return collect($targetWidths)->unique()->sort()->values()->toArray();
    }

    public function setSizeRatio(float $ratio): self
    {
        $this->sizeRatio = $ratio;

        return $this;
    }

    public function setMinimumWidth(int $width): self
    {
        $this->minimumWidth = $width;

        return $this;
    }

    public function setTargetWidths(int $count): self
    {
        $this->targetWidths = $count;

        return $this;
    }
}

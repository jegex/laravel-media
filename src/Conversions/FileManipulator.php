<?php

namespace Jegex\Media\Conversions;

use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class FileManipulator
{
    public function performManipulations(string $sourcePath, string $destinationPath, array $manipulations): bool
    {
        if (empty($manipulations)) {
            return false;
        }

        $image = Image::load($sourcePath);

        foreach ($manipulations as $name => $parameters) {
            $image = $this->applyManipulation($image, $name, $parameters);
        }

        $image->save($destinationPath);

        return true;
    }

    public function createConversion(string $sourcePath, string $destinationPath, string $format, array $options = []): bool
    {
        try {
            $image = Image::load($sourcePath);

            if (isset($options['width'])) {
                $image->width($options['width']);
            }

            if (isset($options['height'])) {
                $image->height($options['height']);
            }

            if (isset($options['fit'])) {
                $image->fit(Fit::from($options['fit']));
            }

            if (isset($options['quality'])) {
                $image->quality($options['quality']);
            }

            if (isset($options['optimize']) && $options['optimize']) {
                $image->optimize();
            }

            $image->format($format)->save($destinationPath);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    protected function applyManipulation(Image $image, string $name, array $parameters): Image
    {
        return match ($name) {
            'width' => $image->width($parameters[0]),
            'height' => $image->height($parameters[0]),
            'fit' => $image->fit(Fit::from($parameters[0])),
            'quality' => $image->quality($parameters[0]),
            'brightness' => $image->brightness($parameters[0]),
            'contrast' => $image->contrast($parameters[0]),
            'blur' => $image->blur($parameters[0]),
            'gamma' => $image->gamma($parameters[0]),
            'flip' => $image->flip($parameters[0]),
            default => $image,
        };
    }
}

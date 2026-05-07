<?php

namespace Jegex\Media\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jegex\Media\MediaCollections\Models\Media;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'model_type' => 'Jegex\Media\Tests\TestModel',
            'model_id' => 1,
            'uuid' => $this->faker->uuid(),
            'collection_name' => 'default',
            'name' => $this->faker->word(),
            'file_name' => $this->faker->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => $this->faker->numberBetween(1000, 1000000),
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ];
    }
}

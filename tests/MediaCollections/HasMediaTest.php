<?php

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Jegex\Media\MediaCollections\FileAdder\FileAdder;
use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\Tests\TestModel;

it('has media relationship', function () {
    $model = new TestModel;

    expect($model->media())->toBeInstanceOf(MorphMany::class);
});

it('can add media', function () {
    $model = TestModel::create(['name' => 'Test']);

    $fileAdder = $model->addMedia(__FILE__);

    expect($fileAdder)->toBeInstanceOf(FileAdder::class);
});

it('can get media collection', function () {
    $model = TestModel::create(['name' => 'Test']);

    Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    $media = $model->getMedia('default');

    expect($media->count())->toBe(1);
});

it('can get first media', function () {
    $model = TestModel::create(['name' => 'Test']);

    Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    expect($model->getFirstMedia())->toBeInstanceOf(Media::class);
});

it('can check if has media', function () {
    $model = TestModel::create(['name' => 'Test']);

    expect($model->hasMedia('default'))->toBeFalse();

    Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    // Reload model with media relationship
    $model->load('media');

    expect($model->hasMedia('default'))->toBeTrue();
});

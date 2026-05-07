<?php

use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\Tests\TestModel;

it('sets order column on creating', function () {
    $model = TestModel::create(['name' => 'Test']);

    $media1 = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test1',
        'file_name' => 'test1.jpg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    expect($media1->order_column)->toBe(1);

    $media2 = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test2',
        'file_name' => 'test2.jpg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    expect($media2->order_column)->toBe(2);
});

it('can get highest order number', function () {
    $model = TestModel::create(['name' => 'Test']);

    Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test1',
        'file_name' => 'test1.jpg',
        'disk' => 'public',
        'size' => 1000,
        'order_column' => 5,
    ]);

    $media = new Media;
    $media->model_type = TestModel::class;
    $media->model_id = $model->id;

    expect($media->getHighestOrderNumber())->toBe(5);
});

it('can set highest order number', function () {
    $model = TestModel::create(['name' => 'Test']);

    Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test1',
        'file_name' => 'test1.jpg',
        'disk' => 'public',
        'size' => 1000,
        'order_column' => 5,
    ]);

    $media = new Media;
    $media->model_type = TestModel::class;
    $media->model_id = $model->id;
    $media->setHighestOrderNumber();

    expect($media->order_column)->toBe(6);
});

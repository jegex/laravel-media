<?php

use Illuminate\Support\Facades\Storage;
use Jegex\Media\Conversions\Conversion;
use Jegex\Media\Facades\LaravelMedia;
use Jegex\Media\MediaCollections\FileAdder\FileAdder;
use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\Tests\TestModel;
use Spatie\Image\Enums\Fit;

it('can set width on conversion', function () {
    $conversion = Conversion::create('thumb')->width(200);

    expect($conversion->getManipulations())->toHaveKey('width', [200]);
});

it('can set height on conversion', function () {
    $conversion = Conversion::create('thumb')->height(150);

    expect($conversion->getManipulations())->toHaveKey('height', [150]);
});

it('can set fit on conversion', function () {
    $conversion = Conversion::create('thumb')->fit(Fit::Contain);

    expect($conversion->getManipulations())->toHaveKey('fit', ['contain']);
});

it('can set format on conversion', function () {
    $conversion = Conversion::create('thumb')->format('webp');

    expect($conversion->getFormat())->toBe('webp');
});

it('can set quality on conversion', function () {
    $conversion = Conversion::create('thumb')->quality(80);

    expect($conversion->getManipulations())->toHaveKey('quality', [80]);
});

it('can chain multiple manipulations', function () {
    $conversion = Conversion::create('preview')
        ->width(800)
        ->height(600)
        ->quality(90)
        ->optimize();

    expect($conversion->getManipulations())->toHaveKeys(['width', 'height', 'quality'])
        ->and($conversion->shouldOptimize())->toBeTrue()
        ->and($conversion->getFormat())->toBeNull();
});

it('can convert to array', function () {
    $conversion = Conversion::create('thumb')
        ->width(200)
        ->height(200)
        ->optimize()
        ->format('webp');

    $array = $conversion->toArray();

    expect($array)->toHaveKeys(['name', 'manipulations', 'optimize', 'queued', 'format'])
        ->and($array['name'])->toBe('thumb')
        ->and($array['optimize'])->toBeTrue()
        ->and($array['format'])->toBe('webp')
        ->and($array['queued'])->toBeTrue();
});

it('can set explicitly queued', function () {
    $conversion = Conversion::create('thumb')->nonQueued()->queued();

    expect($conversion->shouldBeQueued())->toBeTrue();
});

it('model can register media conversions', function () {
    $model = TestModel::create(['name' => 'Test']);

    $model->addMediaConversion('thumb')
        ->width(200)
        ->height(200)
        ->optimize()
        ->queued();

    $model->addMediaConversion('preview')
        ->width(800)
        ->nonQueued();

    $conversions = $model->getMediaConversions();

    expect($conversions->count())->toBe(2)
        ->and($conversions->getQueuedConversions()->count())->toBe(1)
        ->and($conversions->getNonQueuedConversions()->count())->toBe(1);
});

it('can get first media url', function () {
    $model = TestModel::create(['name' => 'Test']);

    Storage::fake('public');

    $media = $model->addMedia(__DIR__.'/../test-large.jpg')
        ->toMediaCollection('images');

    expect($model->getFirstMediaUrl('images'))->toContain('test-large.jpg');
});

it('can add media from base64 via model', function () {
    $model = TestModel::create(['name' => 'Test']);

    Storage::fake('public');

    $content = file_get_contents(__DIR__.'/../test-large.jpg');
    $base64 = base64_encode($content);

    $fileAdder = $model->addMediaFromBase64($base64, 'photo.jpg');

    expect($fileAdder)->toBeInstanceOf(FileAdder::class);
});

it('can clear all media', function () {
    $model = TestModel::create(['name' => 'Test']);

    Storage::fake('public');

    $model->addMedia(__DIR__.'/../test-large.jpg')
        ->toMediaCollection('images');

    $model->addMedia(__DIR__.'/../test-large.jpg')
        ->toMediaCollection('documents');

    expect($model->media()->count())->toBe(2);

    $model->clearMedia();

    expect($model->media()->count())->toBe(0);
});

it('can copy media to another collection', function () {
    $model = TestModel::create(['name' => 'Test']);

    Storage::fake('public');

    $original = $model->addMedia(__DIR__.'/../test-large.jpg')
        ->toMediaCollection('images');

    $copy = $model->copyMedia($original, 'thumbnails');

    expect($copy->collection_name)->toBe('thumbnails')
        ->and($copy->file_name)->toBe($original->file_name)
        ->and($copy->id)->not->toBe($original->id);
});

it('can use laravel media facade to create from file', function () {
    Storage::fake('public');

    $media = LaravelMedia::createFromFile(__DIR__.'/../test-large.jpg');

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->file_name)->toContain('test-large');
});

it('can use laravel media facade to get media by id', function () {
    Storage::fake('public');

    $media = LaravelMedia::createFromFile(__DIR__.'/../test-large.jpg');

    $found = LaravelMedia::getMediaById($media->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($media->id);
});

it('can use laravel media facade to get default disk', function () {
    expect(LaravelMedia::getDefaultDisk())->toBe('public');
});

it('can use laravel media facade to get max file size', function () {
    expect(LaravelMedia::getMaxFileSize())->toBe(1024 * 1024 * 10);
});

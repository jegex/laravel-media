<?php

use Illuminate\Support\Facades\Storage;
use Jegex\Media\MediaCollections\Models\Media;

it('can create media from file path without model', function () {
    Storage::fake('public');

    $testImagePath = __DIR__.'/../test-image.jpg';
    file_put_contents($testImagePath, 'fake image content');

    $media = Media::createFromFile($testImagePath);

    expect($media->id)->toBeGreaterThan(0)
        ->and($media->model_type)->toBeNull()
        ->and($media->model_id)->toBeNull()
        ->and($media->collection_name)->toBe('default')
        ->and($media->file_name)->toContain('test-image')
        ->and($media->name)->toBe('test-image');

    @unlink($testImagePath);
});

it('can create media from string content', function () {
    Storage::fake('public');

    $media = Media::createFromString('image content here', 'photo.jpg');

    expect($media->id)->toBeGreaterThan(0)
        ->and($media->model_type)->toBeNull()
        ->and($media->model_id)->toBeNull()
        ->and($media->file_name)->toContain('photo')
        ->and($media->file_name)->toContain('jpg');
});

it('can create media from base64', function () {
    Storage::fake('public');

    $content = base64_encode('base64 image data');

    $media = Media::createFromBase64($content, 'avatar.png');

    expect($media->id)->toBeGreaterThan(0)
        ->and($media->model_type)->toBeNull()
        ->and($media->model_id)->toBeNull()
        ->and($media->file_name)->toContain('avatar')
        ->and($media->file_name)->toContain('png');
});

it('throws exception for invalid base64', function () {
    Media::createFromBase64('not-valid-base64!!!', 'test.jpg');
})->throws(InvalidArgumentException::class);

it('can create media with custom options', function () {
    Storage::fake('public');

    $media = Media::createFromString('content', 'custom.jpg', [
        'collection_name' => 'uploads',
        'name' => 'My Custom Image',
        'custom_properties' => ['source' => 'api'],
    ]);

    expect($media->collection_name)->toBe('uploads')
        ->and($media->name)->toBe('My Custom Image')
        ->and($media->getCustomProperty('source'))->toBe('api');
});

it('throws exception when file not found', function () {
    Media::createFromFile('/non/existent/path/file.jpg');
})->throws(InvalidArgumentException::class);

it('throws exception when file exceeds max size', function () {
    Storage::fake('public');
    config()->set('media.max_file_size', 100);

    $testImagePath = __DIR__.'/../test-large.jpg';
    file_put_contents($testImagePath, str_repeat('x', 200));

    Media::createFromFile($testImagePath);

    @unlink($testImagePath);
})->throws(InvalidArgumentException::class);

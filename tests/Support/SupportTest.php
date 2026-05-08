<?php

use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\Support\FileNamer\DefaultFileNamer;
use Jegex\Media\Support\PathGenerator\DefaultPathGenerator;
use Jegex\Media\Support\UrlGenerator\DefaultUrlGenerator;

it('can get responsive file name', function () {
    $fileNamer = new DefaultFileNamer;

    expect($fileNamer->responsiveFileName('test.jpg'))->toBe('test')
        ->and($fileNamer->responsiveFileName('my-image.png'))->toBe('my-image');
});

it('can get temporary directory postfix', function () {
    $fileNamer = new DefaultFileNamer;
    $media = new Media;
    $media->id = 123;

    expect($fileNamer->getTemporaryDirectoryPostfix($media))->toBe('media-123');
});

it('can get path', function () {
    $pathGenerator = new DefaultPathGenerator;
    $media = new Media;
    $media->uuid = '550e8400-e29b-41d4-a716-446655440000';

    expect($pathGenerator->getPath($media))->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('can get path for conversions', function () {
    $pathGenerator = new DefaultPathGenerator;
    $media = new Media;
    $media->uuid = '550e8400-e29b-41d4-a716-446655440000';

    expect($pathGenerator->getPathForConversions($media))->toBe('550e8400-e29b-41d4-a716-446655440000/conversions');
});

it('can get path for responsive images', function () {
    $pathGenerator = new DefaultPathGenerator;
    $media = new Media;
    $media->uuid = '550e8400-e29b-41d4-a716-446655440000';

    expect($pathGenerator->getPathForResponsiveImages($media))->toBe('550e8400-e29b-41d4-a716-446655440000/responsive-images');
});

it('can apply prefix to path', function () {
    config()->set('media.prefix', 'my-prefix');

    $pathGenerator = new DefaultPathGenerator;
    $media = new Media;
    $media->uuid = '550e8400-e29b-41d4-a716-446655440000';

    expect($pathGenerator->getPath($media))->toBe('my-prefix550e8400-e29b-41d4-a716-446655440000');
});

it('url generator uses disk', function () {
    $urlGenerator = new DefaultUrlGenerator;

    expect($urlGenerator)->toBeInstanceOf(DefaultUrlGenerator::class);
});

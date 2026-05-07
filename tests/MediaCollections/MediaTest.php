<?php

use Jegex\Media\MediaCollections\Models\Media;

it('can be instantiated', function () {
    $media = new Media;
    expect($media)->toBeInstanceOf(Media::class);
});

it('has correct casts', function () {
    $media = new Media;

    expect($media->getCasts())->toMatchArray([
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
        'responsive_images' => 'array',
    ]);
});

it('can get custom property', function () {
    $media = new Media;
    $media->custom_properties = ['foo' => 'bar'];

    expect($media->getCustomProperty('foo'))->toBe('bar')
        ->and($media->getCustomProperty('nonexistent'))->toBeNull();
});

it('can set custom property', function () {
    $media = new Media;
    $media->setCustomProperty('key', 'value');

    expect($media->custom_properties)->toHaveKey('key', 'value');
});

it('can check if custom property exists', function () {
    $media = new Media;
    $media->custom_properties = ['foo' => 'bar'];

    expect($media->hasCustomProperty('foo'))->toBeTrue()
        ->and($media->hasCustomProperty('nonexistent'))->toBeFalse();
});

it('can get manipulation', function () {
    $media = new Media;
    $media->manipulations = ['thumb' => ['width' => 100]];

    expect($media->getManipulation('thumb'))->toBe(['width' => 100])
        ->and($media->getManipulation('nonexistent'))->toBeNull();
});

it('can get disk name', function () {
    $media = new Media;
    $media->disk = 's3';

    expect($media->getDiskName())->toBe('s3');
});

it('can get conversions disk name', function () {
    $media = new Media;
    $media->conversions_disk = 's3-conversions';

    expect($media->getConversionsDiskName())->toBe('s3-conversions');
});

it('uses default disk when disk is null', function () {
    config()->set('media.disk_name', 'custom-disk');

    $media = new Media;

    expect($media->getDiskName())->toBe('custom-disk')
        ->and($media->getConversionsDiskName())->toBe('custom-disk');
});

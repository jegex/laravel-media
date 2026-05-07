<?php

use Jegex\Media\Conversions\Conversion;
use Jegex\Media\Conversions\ImageGenerators\Avif;
use Jegex\Media\Conversions\ImageGenerators\GenericImage;
use Jegex\Media\Conversions\ImageGenerators\Pdf;
use Jegex\Media\Conversions\ImageGenerators\Svg;
use Jegex\Media\Conversions\ImageGenerators\Video;
use Jegex\Media\Conversions\ImageGenerators\Webp;

it('can create conversion', function () {
    $conversion = Conversion::create('thumb');

    expect($conversion->getName())->toBe('thumb');
});

it('can add manipulations to conversion', function () {
    $conversion = Conversion::create('thumb')
        ->manipulate('width', 100)
        ->manipulate('height', 100);

    expect($conversion->getManipulations())->toHaveKeys(['width', 'height']);
});

it('can set optimization', function () {
    $conversion = Conversion::create('thumb')->optimize();

    expect($conversion->shouldOptimize())->toBeTrue();
});

it('can set queued or non-queued', function () {
    $conversion = Conversion::create('thumb')->nonQueued();

    expect($conversion->shouldBeQueued())->toBeFalse();
});

it('generic image can handle jpeg', function () {
    $generator = new GenericImage;

    expect($generator->canConvert('image/jpeg'))->toBeTrue()
        ->and($generator->canConvert('image/png'))->toBeTrue()
        ->and($generator->canConvert('image/gif'))->toBeTrue()
        ->and($generator->canConvert('image/webp'))->toBeFalse();
});

it('webp generator can handle webp', function () {
    $generator = new Webp;

    expect($generator->canConvert('image/webp'))->toBeTrue()
        ->and($generator->canConvert('image/jpeg'))->toBeFalse();
});

it('avif generator can handle avif', function () {
    $generator = new Avif;

    expect($generator->canConvert('image/avif'))->toBeTrue()
        ->and($generator->canConvert('image/jpeg'))->toBeFalse();
});

it('pdf generator can handle pdf', function () {
    $generator = new Pdf;

    expect($generator->canConvert('application/pdf'))->toBeTrue()
        ->and($generator->canConvert('image/jpeg'))->toBeFalse();
});

it('svg generator can handle svg', function () {
    $generator = new Svg;

    expect($generator->canConvert('image/svg+xml'))->toBeTrue()
        ->and($generator->canConvert('image/jpeg'))->toBeFalse();
});

it('video generator can handle video', function () {
    $generator = new Video;

    expect($generator->canConvert('video/mp4'))->toBeTrue()
        ->and($generator->canConvert('video/webm'))->toBeTrue()
        ->and($generator->canConvert('image/jpeg'))->toBeFalse();
});

<?php

use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\Tests\TestModel;

it('can get default temporary url lifetime', function () {
    $model = TestModel::create(['name' => 'Test']);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    config()->set('media.temporary_url_default_lifetime', 10);

    expect(config('media.temporary_url_default_lifetime'))->toBe(10);
});

it('can render media to html', function () {
    $model = TestModel::create(['name' => 'Test']);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    $html = $media->toHtml();

    expect($html)->toContain('<img')
        ->and($html)->toContain('src=')
        ->and($html)->toContain('alt=');
});

it('can render media with lazy loading', function () {
    config()->set('media.default_loading_attribute_value', 'lazy');

    $model = TestModel::create(['name' => 'Test']);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    $html = $media->toHtml();

    expect($html)->toContain('loading="lazy"');
});

it('can render media without loading attribute when null', function () {
    config()->set('media.default_loading_attribute_value', null);

    $model = TestModel::create(['name' => 'Test']);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    $html = $media->toHtml();

    expect($html)->not->toContain('loading=');
});

it('can cast media to string', function () {
    $model = TestModel::create(['name' => 'Test']);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    expect((string) $media)->toContain('<img');
});

it('can get url with conversion name', function () {
    $model = TestModel::create(['name' => 'Test']);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    $url = $media->getUrl('thumb');

    expect($url)->toContain('thumb');
});

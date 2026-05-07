<?php

use Illuminate\Support\Facades\Storage;
use Jegex\Media\MediaCollections\MediaCollectionZip;
use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\Tests\TestModel;

beforeEach(function () {
    Storage::fake('public');
});

it('can create zip instance from collection', function () {
    $model = TestModel::create(['name' => 'Test']);

    $media1 = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'image1',
        'file_name' => 'image1.jpg',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 100,
    ]);

    $media2 = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'image2',
        'file_name' => 'image2.png',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 200,
    ]);

    $zip = MediaCollectionZip::create($model->getMedia('default'));

    expect($zip)->toBeInstanceOf(MediaCollectionZip::class);
});

it('can set custom zip file name', function () {
    $zip = MediaCollectionZip::create(collect([]));

    $result = $zip->zipFileName('my-custom-archive');

    expect($result)->toBeInstanceOf(MediaCollectionZip::class);
});

it('can save zip to disk', function () {
    Storage::fake('public');
    Storage::fake('temp');

    $model = TestModel::create(['name' => 'Test']);

    $content = 'fake document content';
    Storage::disk('public')->put('1/document.pdf', $content);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'document',
        'file_name' => 'document.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => strlen($content),
    ]);

    $path = $model->getMediaCollectionZip('default')
        ->zipFileName('test-archive')
        ->saveToDisk('temp');

    expect(Storage::disk('temp')->exists($path))->toBeTrue();
});

it('can filter conversions to include', function () {
    $zip = MediaCollectionZip::create(collect([]));

    $result = $zip->withConversions(['thumb', 'medium']);

    expect($result)->toBeInstanceOf(MediaCollectionZip::class);
});

it('can filter conversions to exclude', function () {
    $zip = MediaCollectionZip::create(collect([]));

    $result = $zip->withoutConversions(['large', 'original']);

    expect($result)->toBeInstanceOf(MediaCollectionZip::class);
});

it('can include responsive images flag', function () {
    $zip = MediaCollectionZip::create(collect([]));

    $result = $zip->withResponsiveImages();

    expect($result)->toBeInstanceOf(MediaCollectionZip::class);
});

it('can set filename callback', function () {
    $zip = MediaCollectionZip::create(collect([]));

    $callback = fn ($media, $defaultFileName) => 'custom-'.$defaultFileName;

    $result = $zip->withFilenameCallback($callback);

    expect($result)->toBeInstanceOf(MediaCollectionZip::class);
});

it('can get zip from standalone media using Media::getZip', function () {
    Storage::fake('public');

    $content = 'standalone document content';
    Storage::disk('public')->put('1/standalone.pdf', $content);

    $media = Media::create([
        'model_type' => null,
        'model_id' => null,
        'collection_name' => 'gallery',
        'name' => 'standalone',
        'file_name' => 'standalone.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => strlen($content),
    ]);

    $zip = Media::getZip(collectionName: 'gallery');

    expect($zip)->toBeInstanceOf(MediaCollectionZip::class);
});

it('can get zip by media ids', function () {
    Storage::fake('public');

    $model = TestModel::create(['name' => 'Test']);

    $content = 'document content';
    Storage::disk('public')->put('1/doc1.pdf', $content);
    Storage::disk('public')->put('2/doc2.pdf', $content);

    $media1 = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'doc1',
        'file_name' => 'doc1.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => strlen($content),
    ]);

    $media2 = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'doc2',
        'file_name' => 'doc2.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => strlen($content),
    ]);

    $zip = Media::getZip(mediaIds: [$media1->id, $media2->id]);

    expect($zip)->toBeInstanceOf(MediaCollectionZip::class);
});

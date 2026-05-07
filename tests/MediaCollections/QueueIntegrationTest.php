<?php

use Illuminate\Support\Facades\Queue;
use Jegex\Media\Conversions\ConversionCollection;
use Jegex\Media\Conversions\Jobs\PerformConversionsJob;
use Jegex\Media\MediaCollections\Models\Media;
use Jegex\Media\ResponsiveImages\Jobs\GenerateResponsiveImagesJob;
use Jegex\Media\Tests\TestModel;

beforeEach(function () {
    Queue::fake();
});

it('dispatches conversion job on media created', function () {
    config()->set('media.conversions', [
        'thumb' => [
            'name' => 'thumb',
            'manipulations' => ['width' => 200],
            'queued' => true,
        ],
    ]);

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

    Queue::assertPushed(PerformConversionsJob::class);
});

it('dispatches responsive images job on image media created', function () {
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

    Queue::assertPushed(GenerateResponsiveImagesJob::class);
});

it('does not dispatch responsive images job for non-image media', function () {
    $model = TestModel::create(['name' => 'Test']);

    $media = Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 1000,
    ]);

    Queue::assertNotPushed(GenerateResponsiveImagesJob::class);
});

it('uses correct queue connection from config', function () {
    config()->set('media.queue_connection_name', 'redis');
    config()->set('media.conversions', [
        'thumb' => ['name' => 'thumb', 'queued' => true],
    ]);

    $model = TestModel::create(['name' => 'Test']);

    Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    Queue::assertPushed(PerformConversionsJob::class, function ($job) {
        return $job->connection === 'redis';
    });
});

it('uses correct queue name from config', function () {
    config()->set('media.queue_name', 'media');
    config()->set('media.conversions', [
        'thumb' => ['name' => 'thumb', 'queued' => true],
    ]);

    $model = TestModel::create(['name' => 'Test']);

    Media::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'collection_name' => 'default',
        'name' => 'test',
        'file_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'size' => 1000,
    ]);

    Queue::assertPushed(PerformConversionsJob::class, function ($job) {
        return $job->queue === 'media';
    });
});

it('conversion collection can filter queued conversions', function () {
    config()->set('media.conversions', [
        'thumb' => ['name' => 'thumb', 'queued' => true],
        'small' => ['name' => 'small', 'queued' => false],
    ]);

    $collection = ConversionCollection::createForMedia(new Media);

    expect($collection->getQueuedConversions()->count())->toBe(1)
        ->and($collection->getNonQueuedConversions()->count())->toBe(1);
});

it('conversion collection uses default queue setting', function () {
    config()->set('media.conversions', [
        'thumb' => ['name' => 'thumb'],
    ]);
    config()->set('media.queue_conversions_by_default', true);

    $collection = ConversionCollection::createForMedia(new Media);

    expect($collection->getQueuedConversions()->count())->toBe(1);
});

it('can check if should run after commit', function () {
    config()->set('media.queue_conversions_after_database_commit', true);

    $collection = ConversionCollection::createForMedia(new Media);

    expect($collection->shouldRunAfterCommit())->toBeTrue();
});

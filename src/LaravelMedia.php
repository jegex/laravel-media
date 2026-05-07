<?php

namespace Jegex\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Jegex\Media\MediaCollections\FileAdder\FileAdder;
use Jegex\Media\MediaCollections\Models\Media;

class LaravelMedia
{
    public function addMedia($file): FileAdder
    {
        return app(FileAdder::class)->addMedia($file);
    }

    public function addMediaFromString(string $content): FileAdder
    {
        return app(FileAdder::class)->addMediaFromString($content);
    }

    public function addMediaFromUrl(string $url): FileAdder
    {
        return app(FileAdder::class)->addMediaFromUrl($url);
    }

    public function addMediaFromBase64(string $base64Content, string $fileName): FileAdder
    {
        $content = base64_decode($base64Content, true);

        if ($content === false) {
            throw new \InvalidArgumentException('Invalid base64 content');
        }

        return app(FileAdder::class)
            ->addMediaFromString($content)
            ->usingFileName($fileName);
    }

    public function createFromFile(string $filePath, array $options = []): Media
    {
        return Media::createFromFile($filePath, $options);
    }

    public function createFromString(string $content, string $fileName, array $options = []): Media
    {
        return Media::createFromString($content, $fileName, $options);
    }

    public function createFromBase64(string $base64Content, string $fileName, array $options = []): Media
    {
        return Media::createFromBase64($base64Content, $fileName, $options);
    }

    public function createFromUrl(string $url, array $options = []): Media
    {
        return Media::createFromUrl($url, $options);
    }

    public function getMediaById(int $id): ?Media
    {
        return Media::find($id);
    }

    public function getMediaByIds(array $ids): Collection
    {
        return Media::whereIn('id', $ids)->get();
    }

    public function getMediaByCollection(string $collectionName): Collection
    {
        return Media::where('collection_name', $collectionName)->get();
    }

    public function deleteMedia(int $id): bool
    {
        $media = Media::find($id);

        if (! $media) {
            return false;
        }

        return $media->delete();
    }

    public function moveMedia(int $mediaId, Model $toModel, string $toCollection = 'default'): Media
    {
        $media = Media::findOrFail($mediaId);
        $media->model()->associate($toModel);
        $media->collection_name = $toCollection;
        $media->save();

        return $media;
    }

    public function copyMediaToDisk(int $mediaId, string $toDisk): Media
    {
        $media = Media::findOrFail($mediaId);
        $sourceDisk = Storage::disk($media->disk);
        $destDisk = Storage::disk($toDisk);
        $sourcePath = $media->getPath().'/'.$media->file_name;
        $destPath = $media->getPath().'/'.$media->file_name;

        $destDisk->writeStream($destPath, $sourceDisk->readStream($sourcePath));

        $media->disk = $toDisk;
        $media->conversions_disk = $toDisk;
        $media->save();

        return $media;
    }

    public function getMaxFileSize(): int
    {
        return config('media.max_file_size', 1024 * 1024 * 10);
    }

    public function getDefaultDisk(): string
    {
        return config('media.disk_name', 'public');
    }
}

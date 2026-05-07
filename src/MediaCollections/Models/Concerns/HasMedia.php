<?php

namespace Jegex\Media\MediaCollections\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jegex\Media\Conversions\Conversion;
use Jegex\Media\Conversions\ConversionCollection;
use Jegex\Media\MediaCollections\FileAdder\FileAdder;
use Jegex\Media\MediaCollections\MediaCollectionZip;
use Jegex\Media\MediaCollections\Models\Media;

trait HasMedia
{
    protected array $mediaConversions = [];

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function addMedia($file): FileAdder
    {
        return app(FileAdder::class)
            ->setSubject($this)
            ->addMedia($file);
    }

    public function addMediaFromString(string $content): FileAdder
    {
        return app(FileAdder::class)
            ->setSubject($this)
            ->addMediaFromString($content);
    }

    public function addMediaFromUrl(string $url): FileAdder
    {
        return app(FileAdder::class)
            ->setSubject($this)
            ->addMediaFromUrl($url);
    }

    public function addMediaFromRequest(string $key): FileAdder
    {
        $request = request();
        $file = $request->file($key);

        if (! $file instanceof UploadedFile) {
            throw new \InvalidArgumentException("No file found in request for key: {$key}");
        }

        return app(FileAdder::class)
            ->setSubject($this)
            ->addMedia($file);
    }

    public function addMediaFromBase64(string $base64Content, string $fileName): FileAdder
    {
        $content = base64_decode($base64Content, true);

        if ($content === false) {
            throw new \InvalidArgumentException('Invalid base64 content');
        }

        return app(FileAdder::class)
            ->setSubject($this)
            ->addMediaFromString($content)
            ->usingFileName($fileName);
    }

    public function getMedia(string $collectionName = 'default', $filters = [])
    {
        if (empty($filters)) {
            return $this->media
                ->where('collection_name', $collectionName);
        }

        return $this->media
            ->where('collection_name', $collectionName)
            ->filter(function (Media $media) use ($filters) {
                foreach ($filters as $property => $value) {
                    if ($media->getCustomProperty($property) !== $value) {
                        return false;
                    }
                }

                return true;
            });
    }

    public function getFirstMedia(string $collectionName = 'default'): ?Media
    {
        return $this->getMedia($collectionName)->first();
    }

    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        $media = $this->getFirstMedia($collectionName);

        if (! $media) {
            return '';
        }

        return $media->getUrl($conversionName);
    }

    public function hasMedia(string $collectionName = 'default'): bool
    {
        return $this->getMedia($collectionName)->count() > 0;
    }

    public function clearMediaCollection(string $collectionName = 'default'): void
    {
        $this->getMedia($collectionName)->each(fn (Media $media) => $media->delete());
    }

    public function clearMedia(): void
    {
        $this->media()->delete();
    }

    public function copyMedia(Media $media, string $toCollection = 'default'): Media
    {
        $disk = config('media.disk_name', 'public');

        $newMedia = new Media;
        $newMedia->model()->associate($this);
        $newMedia->uuid = Str::uuid();
        $newMedia->collection_name = $toCollection;
        $newMedia->name = $media->name;
        $newMedia->file_name = $media->file_name;
        $newMedia->mime_type = $media->mime_type;
        $newMedia->disk = $disk;
        $newMedia->conversions_disk = $disk;
        $newMedia->size = $media->size;
        $newMedia->manipulations = $media->manipulations;
        $newMedia->custom_properties = $media->custom_properties;
        $newMedia->generated_conversions = [];
        $newMedia->responsive_images = [];

        $newMedia->save();

        $sourceDisk = Storage::disk($media->disk);
        $destDisk = Storage::disk($disk);
        $sourcePath = $media->getPath().'/'.$media->file_name;
        $destPath = $newMedia->getPath().'/'.$media->file_name;

        if ($sourceDisk->exists($sourcePath)) {
            $destDisk->writeStream($destPath, $sourceDisk->readStream($sourcePath));
        }

        return $newMedia;
    }

    public function getMediaCollectionZip(string $collectionName = 'default'): MediaCollectionZip
    {
        return MediaCollectionZip::create($this->getMedia($collectionName));
    }

    public function addMediaConversion(string $name): Conversion
    {
        $conversion = new Conversion($name);
        $this->mediaConversions[$name] = $conversion;

        return $conversion;
    }

    public function getMediaConversions(): ConversionCollection
    {
        $this->registerMediaConversions();

        return ConversionCollection::createForMedia(
            $this,
            $this->mediaConversions
        );
    }

    public function registerMediaConversions(?Media $media = null): void {}
}

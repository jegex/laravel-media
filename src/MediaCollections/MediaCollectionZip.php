<?php

namespace Jegex\Media\MediaCollections;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jegex\Media\MediaCollections\Models\Media;
use ZipStream\ZipStream;

class MediaCollectionZip
{
    protected Collection $media;

    protected string $zipFileName;

    protected array $includeConversions = [];

    protected array $excludeConversions = [];

    protected bool $includeResponsiveImages = false;

    protected ?Closure $filenameCallback = null;

    public function __construct(Collection $media)
    {
        $this->media = $media;
        $this->zipFileName = 'media-'.Str::slug(now()->format('Y-m-d_His')).'.zip';
    }

    public static function create(Collection $media): self
    {
        return new self($media);
    }

    public function zipFileName(string $name): self
    {
        $this->zipFileName = Str::endsWith($name, '.zip') ? $name : $name.'.zip';

        return $this;
    }

    public function withConversions(array $conversionNames): self
    {
        $this->includeConversions = $conversionNames;

        return $this;
    }

    public function withoutConversions(array $conversionNames): self
    {
        $this->excludeConversions = $conversionNames;

        return $this;
    }

    public function withResponsiveImages(): self
    {
        $this->includeResponsiveImages = true;

        return $this;
    }

    public function withFilenameCallback(Closure $callback): self
    {
        $this->filenameCallback = $callback;

        return $this;
    }

    public function download(): void
    {
        $zip = new ZipStream(
            outputName: $this->zipFileName,
            sendHttpHeaders: true,
        );

        $this->addMediaToZip($zip);

        $zip->finish();
    }

    public function saveToDisk(string $disk, ?string $path = null): string
    {
        $tempPath = $path ?? $this->zipFileName;

        $stream = fopen('php://temp', 'w+');

        $zip = new ZipStream(
            outputStream: $stream,
            outputName: $this->zipFileName,
            sendHttpHeaders: false,
        );

        $this->addMediaToZip($zip);
        $zip->finish();

        rewind($stream);

        Storage::disk($disk)->writeStream($tempPath, $stream);

        fclose($stream);

        return $tempPath;
    }

    public function getZipStream(): ZipStream
    {
        $zip = new ZipStream(
            outputName: $this->zipFileName,
            sendHttpHeaders: false,
        );

        $this->addMediaToZip($zip);
        $zip->finish();

        return $zip;
    }

    protected function addMediaToZip(ZipStream $zip): void
    {
        $this->media->each(function (Media $media) use ($zip) {
            $this->addMediaFileToZip($media, $zip);

            foreach ($this->includeConversions as $conversionName) {
                $this->addConversionToZip($media, $conversionName, $zip);
            }
        });
    }

    protected function addMediaFileToZip(Media $media, ZipStream $zip): void
    {
        $disk = Storage::disk($media->getDiskName());
        $filePath = $media->getPath().'/'.$media->file_name;

        if (! $disk->exists($filePath)) {
            return;
        }

        $fileName = $this->getFileName($media, $media->file_name);

        $stream = $disk->readStream($filePath);
        $zip->addFileFromStream($fileName, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    protected function addConversionToZip(Media $media, string $conversionName, ZipStream $zip): void
    {
        if (in_array($conversionName, $this->excludeConversions)) {
            return;
        }

        $disk = Storage::disk($media->getConversionsDiskName());
        $pathGenerator = config('media.path_generator');
        $basePath = app($pathGenerator)->getPathForConversions($media);
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $conversionPath = $basePath.'/'.$conversionName.'.'.$extension;

        if (! $disk->exists($conversionPath)) {
            return;
        }

        $fileName = $this->getFileName($media, $conversionName.'.'.$extension, 'conversions');

        $stream = $disk->readStream($conversionPath);
        $zip->addFileFromStream($fileName, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    protected function getFileName(Media $media, string $defaultFileName, ?string $prefix = null): string
    {
        if ($this->filenameCallback) {
            return ($this->filenameCallback)($media, $defaultFileName, $prefix);
        }

        $name = $prefix ? $prefix.'/'.$defaultFileName : $defaultFileName;

        if ($media->model_type && $media->model_id) {
            $modelBaseName = class_basename($media->model_type);

            return $prefix
                ? $prefix.'/'.$modelBaseName.'-'.$media->model_id.'-'.$defaultFileName
                : $modelBaseName.'-'.$media->model_id.'-'.$defaultFileName;
        }

        return $name;
    }
}

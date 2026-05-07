<?php

namespace Jegex\Media\MediaCollections\FileAdder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Jegex\Media\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileAdder
{
    protected ?Model $subject = null;

    protected string $collectionName = 'default';

    protected string $name = '';

    protected string $fileName = '';

    protected mixed $file = null;

    protected string $fileType = '';

    protected array $customProperties = [];

    protected array $manipulations = [];

    protected ?string $diskName = null;

    protected ?string $conversionsDiskName = null;

    protected bool $generateResponsiveImages = false;

    public function setSubject(Model $model): self
    {
        $this->subject = $model;

        return $this;
    }

    public function usingName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function usingFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function toMediaCollection(string $collectionName = 'default', string $diskName = ''): Media
    {
        if (! $this->file instanceof UploadedFile && ! is_string($this->file)) {
            throw new InvalidArgumentException('You must provide a file before calling toMediaCollection().');
        }

        $this->collectionName = $collectionName;
        $this->diskName = $diskName ?: null;

        return $this->toMedia();
    }

    public function addMedia(UploadedFile|string $file): self
    {
        if (is_string($file)) {
            return $this->fromString($file);
        }

        $this->file = $file;
        $this->fileType = $file->getMimeType();

        if (empty($this->name)) {
            $this->name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        if (empty($this->fileName)) {
            $this->fileName = $this->getSanitizedFileName($file->getClientOriginalName());
        }

        return $this;
    }

    public function addMediaFromString(string $content): self
    {
        $file = $this->createTemporaryFile($content);

        return $this->fromString($file);
    }

    public function addMediaFromUrl(string $url): self
    {
        $downloader = config('media.media_downloader');
        $downloaderInstance = app($downloader);

        $tempFile = $downloaderInstance->getTempFile($url);

        return $this->fromString($tempFile);
    }

    public function withCustomProperties(array $customProperties): self
    {
        $this->customProperties = $customProperties;

        return $this;
    }

    public function withManipulations(array $manipulations): self
    {
        $this->manipulations = $manipulations;

        return $this;
    }

    public function onDisk(string $diskName): self
    {
        $this->diskName = $diskName;

        return $this;
    }

    public function onConversionsDisk(string $diskName): self
    {
        $this->conversionsDiskName = $diskName;

        return $this;
    }

    public function withResponsiveImages(): self
    {
        $this->generateResponsiveImages = true;

        return $this;
    }

    public function addMediaFromRequest(string $key): self
    {
        $request = request();
        $file = $request->file($key);

        if (! $file instanceof UploadedFile) {
            throw new InvalidArgumentException("No file found in request for key: {$key}");
        }

        return $this->addMedia($file);
    }

    public function addMediaFromBase64(string $base64Content, string $fileName): self
    {
        $content = base64_decode($base64Content, true);

        if ($content === false) {
            throw new InvalidArgumentException('Invalid base64 content');
        }

        return $this->addMediaFromString($content)
            ->usingFileName($fileName);
    }

    protected function fromString(string $path): self
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("File does not exist at: {$path}");
        }

        $this->file = new UploadedFile(
            $path,
            basename($path),
            mime_content_type($path),
            null,
            true
        );

        $this->fileType = $this->file->getMimeType();

        if (empty($this->name)) {
            $this->name = pathinfo(basename($path), PATHINFO_FILENAME);
        }

        if (empty($this->fileName)) {
            $this->fileName = $this->getSanitizedFileName(basename($path));
        }

        return $this;
    }

    protected function toMedia(): Media
    {
        if (is_string($this->file)) {
            $this->file = new UploadedFile(
                $this->file,
                basename($this->file),
                mime_content_type($this->file),
                null,
                true
            );
        }

        $maxFileSize = config('media.max_file_size', 1024 * 1024 * 10);

        if ($this->file->getSize() > $maxFileSize) {
            throw new InvalidArgumentException(
                'File size ('.$this->file->getSize()." bytes) exceeds maximum of {$maxFileSize} bytes."
            );
        }

        $media = new Media;
        $media->model()->associate($this->subject);
        $media->uuid = Str::uuid();
        $media->collection_name = $this->collectionName;
        $media->name = $this->name;
        $media->file_name = $this->fileName;
        $media->mime_type = $this->fileType;
        $media->disk = $this->diskName ?? config('media.disk_name', 'public');
        $media->conversions_disk = $this->conversionsDiskName ?? $media->disk;
        $media->size = $this->file->getSize();
        $media->manipulations = $this->manipulations;
        $media->custom_properties = array_merge(
            $this->customProperties,
            ['responsive' => $this->generateResponsiveImages]
        );
        $media->generated_conversions = [];
        $media->responsive_images = [];

        $this->copyToMediaLibrary($media);

        $media->save();

        return $media;
    }

    protected function copyToMediaLibrary(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $path = $media->getPath();

        $destination = $path.'/'.$media->file_name;

        if ($this->file instanceof UploadedFile) {
            $fileStream = fopen($this->file->getPathname(), 'r');
            $disk->writeStream($destination, $fileStream);
            if (is_resource($fileStream)) {
                fclose($fileStream);
            }
        }
    }

    protected function getSanitizedFileName(string $fileName): string
    {
        $fileNamer = config('media.file_namer');

        return app($fileNamer)->responsiveFileName($fileName).'.'.pathinfo($fileName, PATHINFO_EXTENSION);
    }

    protected function createTemporaryFile(string $content): string
    {
        $temporaryDirectory = config('media.temporary_directory_path')
            ?? storage_path('media-library/temp');

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0755, true);
        }

        $tempFile = tempnam($temporaryDirectory, 'ml');
        file_put_contents($tempFile, $content);

        return $tempFile;
    }
}

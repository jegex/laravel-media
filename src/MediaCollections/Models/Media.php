<?php

namespace Jegex\Media\MediaCollections\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Jegex\Media\MediaCollections\MediaCollectionZip;
use Jegex\Media\MediaCollections\Models\Observers\MediaObserver;

/**
 * @property int $id
 * @property string|null $model_type
 * @property int|null $model_id
 * @property string|null $uuid
 * @property string $collection_name
 * @property string|array|null $name
 * @property string|array|null $alt_txt
 * @property string|array|null $caption
 * @property string|array|null $description
 * @property string $file_name
 * @property string|null $mime_type
 * @property string $disk
 * @property string|null $conversions_disk
 * @property int $size
 * @property array $manipulations
 * @property array $custom_properties
 * @property array $generated_conversions
 * @property array $responsive_images
 * @property int|null $order_column
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Media extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
        'responsive_images' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::observe(MediaObserver::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MediaTranslation::class, 'media_id');
    }

    public static function createFromFile(string $filePath, array $options = []): self
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File does not exist at: {$filePath}");
        }

        return static::createMediaFromPath($filePath, $options);
    }

    public static function createFromString(string $content, string $fileName, array $options = []): self
    {
        $tempPath = static::createTemporaryFile($content, $fileName);

        return static::createMediaFromPath($tempPath, $options);
    }

    public static function createFromBase64(string $base64Content, string $fileName, array $options = []): self
    {
        $content = base64_decode($base64Content, true);

        if ($content === false) {
            throw new InvalidArgumentException('Invalid base64 content');
        }

        return static::createFromString($content, $fileName, $options);
    }

    public static function createFromUrl(string $url, array $options = []): self
    {
        $downloader = config('media.media_downloader');
        $downloaderInstance = app($downloader);

        $tempFile = $downloaderInstance->getTempFile($url);

        return static::createMediaFromPath($tempFile, $options);
    }

    protected static function createMediaFromPath(string $filePath, array $options): self
    {
        $maxFileSize = config('media.max_file_size', 1024 * 1024 * 10);
        $fileSize = filesize($filePath);

        if ($fileSize > $maxFileSize) {
            throw new InvalidArgumentException(
                "File size ({$fileSize} bytes) exceeds maximum of {$maxFileSize} bytes."
            );
        }

        $disk = $options['disk'] ?? config('media.disk_name', 'public');
        $collectionName = $options['collection_name'] ?? 'default';
        $name = $options['name'] ?? pathinfo($filePath, PATHINFO_FILENAME);
        $fileName = $options['file_name'] ?? static::sanitizeFileName(basename($filePath));
        $customProperties = $options['custom_properties'] ?? [];
        $manipulations = $options['manipulations'] ?? [];

        $media = new self;
        $media->model_type = null;
        $media->model_id = null;
        $media->uuid = Str::uuid();
        $media->collection_name = $collectionName;
        $media->name = $name;
        $media->file_name = $fileName;
        $media->mime_type = mime_content_type($filePath);
        $media->disk = $disk;
        $media->conversions_disk = $disk;
        $media->size = $fileSize;
        $media->manipulations = $manipulations;
        $media->custom_properties = $customProperties;
        $media->generated_conversions = [];
        $media->responsive_images = [];

        $storageDisk = Storage::disk($disk);
        $destination = $media->getPath().'/'.$fileName;

        $fileStream = fopen($filePath, 'r');
        $storageDisk->writeStream($destination, $fileStream);
        if (is_resource($fileStream)) {
            fclose($fileStream);
        }

        $media->save();

        return $media;
    }

    protected static function sanitizeFileName(string $fileName): string
    {
        $fileNamer = config('media.file_namer');

        return app($fileNamer)->responsiveFileName($fileName).'.'.pathinfo($fileName, PATHINFO_EXTENSION);
    }

    protected static function createTemporaryFile(string $content, string $fileName): string
    {
        $temporaryDirectory = config('media.temporary_directory_path')
            ?? storage_path('media-library/temp');

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0755, true);
        }

        $sanitized = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $fileName);
        $tempFile = $temporaryDirectory.'/'.$sanitized;

        file_put_contents($tempFile, $content);

        return $tempFile;
    }

    public function getDiskName(): string
    {
        return $this->disk ?: config('media.disk_name');
    }

    public function getConversionsDiskName(): string
    {
        return $this->conversions_disk ?: config('media.disk_name');
    }

    public function getPath(): string
    {
        $pathGenerator = config('media.path_generator');

        return app($pathGenerator)->getPath($this);
    }

    public function getUrl(?string $conversionName = null): string
    {
        $urlGenerator = config('media.url_generator');

        if ($conversionName) {
            return app($urlGenerator)->getUrlForConversion($this, $conversionName);
        }

        return app($urlGenerator)->getUrl($this);
    }

    public function getTemporaryUrl(?DateTimeInterface $expiration = null): string
    {
        if ($expiration === null) {
            $expiration = now()->addMinutes(
                config('media.temporary_url_default_lifetime', 5)
            );
        }

        $urlGenerator = config('media.url_generator');

        return app($urlGenerator)->getTemporaryUrl($this, $expiration);
    }

    public function getName(): string
    {
        if (config('media.translatable') === 'spatie') {
            return $this->getTranslatableValue('name');
        }

        if (config('media.translatable') === 'astrotomic') {
            return $this->getAstrotomicValue('name');
        }

        if (is_array($this->name)) {
            return $this->name[app()->getLocale()] ?? $this->name['en'] ?? '';
        }

        return $this->name;
    }

    public function getAltTxt(): string
    {
        if (config('media.translatable') === 'spatie') {
            return $this->getTranslatableValue('alt_txt');
        }

        if (config('media.translatable') === 'astrotomic') {
            return $this->getAstrotomicValue('alt_txt');
        }

        if (is_array($this->alt_txt)) {
            return $this->alt_txt[app()->getLocale()] ?? $this->alt_txt['en'] ?? '';
        }

        return $this->alt_txt ?? '';
    }

    public function getCaption(): string
    {
        if (config('media.translatable') === 'spatie') {
            return $this->getTranslatableValue('caption');
        }

        if (config('media.translatable') === 'astrotomic') {
            return $this->getAstrotomicValue('caption');
        }

        if (is_array($this->caption)) {
            return $this->caption[app()->getLocale()] ?? $this->caption['en'] ?? '';
        }

        return $this->caption ?? '';
    }

    public function getDescription(): string
    {
        if (config('media.translatable') === 'spatie') {
            return $this->getTranslatableValue('description');
        }

        if (config('media.translatable') === 'astrotomic') {
            return $this->getAstrotomicValue('description');
        }

        if (is_array($this->description)) {
            return $this->description[app()->getLocale()] ?? $this->description['en'] ?? '';
        }

        return $this->description ?? '';
    }

    protected function getTranslatableValue(string $key): string
    {
        $value = $this->$key;

        if (is_string($value) && json_validate($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return $value ?? '';
        }

        return $value[app()->getLocale()] ?? $value['en'] ?? '';
    }

    protected function getAstrotomicValue(string $key): string
    {
        $locale = app()->getLocale();

        $translation = $this->translations()
            ->where('locale', $locale)
            ->first();

        if ($translation && ! empty($translation->$key)) {
            return $translation->$key;
        }

        $fallbackTranslation = $this->translations()
            ->where('locale', config('app.fallback_locale'))
            ->first();

        if ($fallbackTranslation && ! empty($fallbackTranslation->$key)) {
            return $fallbackTranslation->$key;
        }

        return '';
    }

    public function setTranslation(string $key, string $locale, $value): self
    {
        $translations = $this->$key;

        if (is_string($translations) && json_validate($translations)) {
            $translations = json_decode($translations, true);
        }

        if (! is_array($translations)) {
            $translations = [];
        }

        $translations[$locale] = $value;

        $this->$key = $translations;

        return $this;
    }

    public function getTranslation(string $key, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        $value = $this->$key;

        if (! is_array($value)) {
            return $value ?? '';
        }

        return $value[$locale] ?? $value['en'] ?? '';
    }

    public function setHighestOrderNumber(): void
    {
        $orderColumnName = 'order_column';

        $this->$orderColumnName = $this->getHighestOrderNumber() + 1;
    }

    public function getHighestOrderNumber(): int
    {
        return (int) static::where('model_type', $this->model_type)
            ->where('model_id', $this->model_id)
            ->max('order_column');
    }

    public function getCustomProperty(string $propertyName): mixed
    {
        return $this->custom_properties[$propertyName] ?? null;
    }

    public function getManipulation(string $manipulationName): mixed
    {
        return $this->manipulations[$manipulationName] ?? null;
    }

    public function hasCustomProperty(string $propertyName): bool
    {
        return isset($this->custom_properties[$propertyName]);
    }

    public function setCustomProperty(string $propertyName, mixed $value): self
    {
        $customProperties = $this->custom_properties;
        $customProperties[$propertyName] = $value;
        $this->custom_properties = $customProperties;

        return $this;
    }

    public function toHtml(): string
    {
        $urlGenerator = config('media.url_generator');
        $url = app($urlGenerator)->getUrl($this);

        $loading = config('media.default_loading_attribute_value');
        $loadingAttribute = $loading ? " loading=\"{$loading}\"" : '';

        $alt = $this->getAltTxt();

        return "<img src=\"{$url}\" alt=\"{$alt}\"{$loadingAttribute}>";
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    public static function getZip(array $mediaIds = [], string $collectionName = ''): MediaCollectionZip
    {
        $query = static::query();

        if (! empty($mediaIds)) {
            $query->whereIn('id', $mediaIds);
        }

        if (! empty($collectionName)) {
            $query->where('collection_name', $collectionName);
        }

        return MediaCollectionZip::create($query->get());
    }
}

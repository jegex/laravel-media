# jegex/laravel-media

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jegex/laravel-media.svg?style=flat-square)](https://packagist.org/packages/jegex/laravel-media)
[![Tests](https://github.com/jegex/laravel-media/actions/workflows/run-tests.yml/badge.svg)](https://github.com/jegex/laravel-media/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jegex/laravel-media.svg?style=flat-square)](https://packagist.org/packages/jegex/laravel-media)
[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-red.svg)](https://laravel.com)

Powerful media management package for Laravel applications. Handle file uploads, image conversions, responsive images, and video thumbnails with ease. Inspired by `spatie/laravel-medialibrary`.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Usage](#usage)
  - [Adding Media](#adding-media)
  - [Retrieving Media](#retrieving-media)
  - [Media Properties](#media-properties)
  - [Rendering Media](#rendering-media)
- [Image Conversions](#image-conversions)
- [Responsive Images](#responsive-images)
- [Queue System](#queue-system)
- [Vapor Uploads](#vapor-uploads)
- [ZIP Export](#zip-export)
- [Facade Usage](#facade-usage)
- [Configuration](#configuration)
- [Environment Variables](#environment-variables)
- [Advanced Usage](#advanced-usage)
- [Testing](#testing)
- [Changelog](#changelog)
- [License](#license)

## Installation

Install the package via Composer:

```bash
composer require jegex/laravel-media
```

### Post-Installation Steps

#### Step 1: Publish Configuration & Migration

```bash
# Publish config file to config/media.php
php artisan vendor:publish --tag="media-config"

# Publish migration file
php artisan vendor:publish --tag="media-migrations"
```

#### Step 2: Run Migration

```bash
php artisan migrate
```

#### Step 4: Configure Storage Disk (Optional)

In `config/media.php` or your `.env` file, set the default storage disk:

```env
MEDIA_DISK=public
```

Then create the symbolic link for public disk:

```bash
php artisan storage:link
```

#### Step 5: Configure Queue (Optional)

If you want image conversions to run asynchronously:

```env
QUEUE_CONNECTION=redis
QUEUE_CONVERSIONS_BY_DEFAULT=true
QUEUE_CONVERSIONS_AFTER_DB_COMMIT=true
```

## Quick Start

Add the `HasMedia` trait to your model:

```php
use Jegex\Media\MediaCollections\Concerns\HasMedia;

class Post extends Model
{
    use HasMedia;

    // ...
}
```

Now you can attach media:

```php
$post = Post::create(['title' => 'My Post']);

$post->addMedia('/path/to/image.jpg')
    ->toMediaCollection('images');

$post->getMedia('images')->first()->getUrl();
```

## Usage

### Adding Media via Model

Add the `HasMedia` trait to your model:

```php
use Jegex\Media\MediaCollections\Concerns\HasMedia;

class Post extends Model
{
    use HasMedia;

    // ...
}
```

Now you can attach media:

```php
$post = Post::create(['title' => 'My Post']);

$post->addMedia('/path/to/image.jpg')
    ->toMediaCollection('images');

$post->getMedia('images')->first()->getUrl();
```

### Adding Media Directly (Standalone)

Since the `model_type` and `model_id` columns are nullable, you can create media without associating it to any model:

```php
use Jegex\Media\MediaCollections\Models\Media;

// From a file path
$media = Media::createFromFile('/path/to/image.jpg');

// From string content
$media = Media::createFromString($imageContent, 'photo.jpg');

// From base64
$media = Media::createFromBase64($base64String, 'avatar.png');

// From a URL
$media = Media::createFromUrl('https://example.com/image.jpg');

// With custom options
$media = Media::createFromFile('/path/to/image.jpg', [
    'collection_name' => 'uploads',
    'name' => 'Custom Name',
    'disk' => 's3',
    'custom_properties' => ['source' => 'api'],
]);

// Access the media
echo $media->getUrl();
echo $media->toHtml();
```

This is useful for global media libraries, CDN assets, or when you don't need to associate media with a specific model.

### Retrieving Media

```php
// Get all media in a collection
$media = $model->getMedia('photos');

// Get first media
$media = $model->getFirstMedia('photos');

// Get media URL
$url = $media->getUrl();

// Get conversion URL
$url = $media->getUrl('thumb');

// Get temporary URL (S3)
$url = $media->getTemporaryUrl(now()->addMinutes(10));
```

### Media Properties

```php
$media->getName();        // 'my-image'
$media->getAltTxt();      // 'Alt text for accessibility'
$media->getCaption();     // 'Short caption'
$media->getDescription(); // 'Full description'
$media->file_name;        // 'my-image.jpg'
$media->mime_type;        // 'image/jpeg'
$media->size;             // 102400 (bytes)
```

### Custom Properties

```php
$media->setCustomProperty('credits', 'John Doe');
$media->getCustomProperty('credits'); // 'John Doe'
$media->hasCustomProperty('credits'); // true
```

### Rendering Media

```php
// Convert to HTML string
echo $media->toHtml();
// <img src="..." alt="..." loading="lazy">

// Blade component
<x-media :media="$media" class="w-full" :loading="'lazy'" />

// With conversion
<x-media :media="$media" conversion="thumb" class="thumbnail" />
```

## Image Conversions

Define conversions in your model:

```php
use Jegex\Media\MediaCollections\Concerns\HasMedia;

class Post extends Model
{
    use HasMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->optimize()
            ->queued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->optimize();
    }
}
```

Retrieve converted images:

```php
$thumbUrl = $media->getUrl('thumb');
$previewUrl = $media->getUrl('preview');
```

### Conversion Methods

| Method | Description |
|--------|-------------|
| `width($px)` | Set width |
| `height($px)` | Set height |
| `fit(Fit $fit)` | Set fit mode (contain, cover, fill, etc.) |
| `format($format)` | Set output format (webp, avif, jpg, png) |
| `quality($quality)` | Set output quality (1-100) |
| `brightness($value)` | Adjust brightness (-100 to 100) |
| `contrast($value)` | Adjust contrast |
| `blur($value)` | Apply blur effect |
| `gamma($value)` | Adjust gamma |
| `flip($direction)` | Flip image (h, v) |
| `optimize()` | Optimize output image |
| `queued()` | Process conversion on queue |
| `nonQueued()` | Process conversion synchronously |
| `manipulate($name, $value)` | Add custom manipulation |

### Image Generators

The package supports multiple file types out of the box:

- **GenericImage** — JPEG, PNG, GIF, BMP
- **Webp** — WebP images
- **Avif** — AVIF images
- **Pdf** — PDF files (thumbnail extraction)
- **Svg** — SVG files
- **Video** — Video files (thumbnail extraction via FFMPEG)

### Image Optimizers

Optimizers are applied automatically when `optimize()` is called:

| Format | Optimizer |
|--------|-----------|
| JPEG | Jpegoptim |
| PNG | Pngquant, Optipng |
| SVG | Svgo |
| GIF | Gifsicle |
| WebP | Cwebp |
| AVIF | Avifenc |

## Responsive Images

Responsive images are generated automatically for image media files.

```php
// Enable responsive images for a collection
$model->addMedia('/path/to/image.jpg')
    ->withResponsiveImages()
    ->toMediaCollection('photos');
```

The package calculates optimal widths using `FileSizeOptimizedWidthCalculator` (30% smaller per variation) and generates a blurred tiny placeholder for progressive loading.

## Queue System

Conversions and responsive images can be processed asynchronously.

### Configuration

```php
// config/media.php
'queue_connection_name' => env('QUEUE_CONNECTION', 'sync'),
'queue_name' => env('MEDIA_QUEUE', ''),
'queue_conversions_by_default' => env('QUEUE_CONVERSIONS_BY_DEFAULT', true),
'queue_conversions_after_database_commit' => env('QUEUE_CONVERSIONS_AFTER_DB_COMMIT', true),
```

### Per-Conversion Queue Setting

```php
$this->addMediaConversion('thumb')
    ->width(200)
    ->queued(); // Process on queue

$this->addMediaConversion('preview')
    ->width(800)
    ->nonQueued(); // Process immediately
```

### Jobs

| Job | Description |
|-----|-------------|
| `PerformConversionsJob` | Processes image conversions |
| `GenerateResponsiveImagesJob` | Generates responsive image variations |

## Vapor Uploads

For Laravel Vapor deployments, enable the upload route:

```php
// config/media.php
'enable_vapor_uploads' => env('ENABLE_MEDIA_LIBRARY_VAPOR_UPLOADS', false),
'vapor_route_prefix' => 'media-vapor',
'vapor_route_middleware' => ['web', 'auth'],
```

### Routes

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/media-vapor` | Store new media from Vapor |
| POST | `/media-vapor/finished/{mediaId}` | Mark media upload as finished |
| POST | `/media-vapor/parameters` | Get upload parameters for S3 direct upload |

## ZIP Export

Export media collections as ZIP archives for bulk downloads.

### Export from Model

```php
// Stream ZIP directly to browser
return $post->getMediaCollectionZip('photos')->download('photos.zip');

// Save ZIP to a disk
$zipPath = $post->getMediaCollectionZip('photos')->saveToDisk('public', 'exports/photos.zip');
```

### Export from Single Media

```php
use Jegex\Media\MediaCollections\Models\Media;

// Get ZIP for a single media item
$zip = $media->getZip('archive.zip');

// Or retrieve media by ID first
$media = Media::find(1);
$zip = $media->getZip('archive.zip');
```

### Filter by Conversion

```php
// Only include 'thumb' conversions in the ZIP
return $post->getMediaCollectionZip('photos', function ($zip, $media) {
    $zip->add($media, 'thumb');
})->download('thumbs.zip');
```

The ZIP export uses `maennchen/zipstream-php` for memory-efficient streaming. Files are added directly to the stream without loading them entirely into memory.

## Facade Usage

The package provides a `LaravelMedia` facade for convenient access to media operations without needing a model:

```php
use Jegex\Media\Facades\LaravelMedia;

// Create media from file
$media = LaravelMedia::createFromFile('/path/to/image.jpg');

// Create media from string content
$media = LaravelMedia::createFromString($imageContent, 'photo.jpg');

// Create media from base64
$media = LaravelMedia::createFromBase64($base64String, 'avatar.png');

// Create media from URL
$media = LaravelMedia::createFromUrl('https://example.com/image.jpg');

// Retrieve media
$media = LaravelMedia::getMediaById(1);
$media = LaravelMedia::getMediaByIds([1, 2, 3]);
$collection = LaravelMedia::getMediaByCollection('avatars');

// Delete media
LaravelMedia::deleteMedia(1);

// Get package info
$maxSize = LaravelMedia::getMaxFileSize(); // 10485760 (10MB)
$defaultDisk = LaravelMedia::getDefaultDisk(); // 'public'
```

## Configuration

The full configuration file (`config/media.php`):

```php
return [
    // Default storage disk
    'disk_name' => env('MEDIA_DISK', 'public'),

    // Maximum file size (10MB default)
    'max_file_size' => 1024 * 1024 * 10,

    // Queue settings
    'queue_connection_name' => env('QUEUE_CONNECTION', 'sync'),
    'queue_name' => env('MEDIA_QUEUE', ''),
    'queue_conversions_by_default' => env('QUEUE_CONVERSIONS_BY_DEFAULT', true),
    'queue_conversions_after_database_commit' => env('QUEUE_CONVERSIONS_AFTER_DB_COMMIT', true),

    // File naming and path generation
    'file_namer' => DefaultFileNamer::class,
    'path_generator' => DefaultPathGenerator::class,
    'file_remover_class' => DefaultFileRemover::class,
    'url_generator' => DefaultUrlGenerator::class,

    // URL versioning
    'version_urls' => false,

    // Image driver: gd, imagick, vips
    'image_driver' => env('IMAGE_DRIVER', 'gd'),

    // FFMPEG settings
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
    'ffmpeg_timeout' => env('FFMPEG_TIMEOUT', 900),
    'ffmpeg_threads' => env('FFMPEG_THREADS', 0),

    // Downloads
    'media_downloader' => DefaultDownloader::class,
    'media_downloader_ssl' => env('MEDIA_DOWNLOADER_SSL', true),

    // Temporary URL lifetime (minutes)
    'temporary_url_default_lifetime' => env('MEDIA_TEMPORARY_URL_DEFAULT_LIFETIME', 5),

    // S3 upload headers
    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=604800',
        ],
    ],

    // Responsive images
    'responsive_images' => [
        'width_calculator' => FileSizeOptimizedWidthCalculator::class,
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => Blurred::class,
    ],

    // Loading attribute: 'lazy', 'eager', 'auto', or null
    'default_loading_attribute_value' => null,

    // Storage prefix
    'prefix' => env('MEDIA_PREFIX', ''),

    // Force lazy loading
    'force_lazy_loading' => env('FORCE_MEDIA_LIBRARY_LAZY_LOADING', true),

    // Vapor uploads
    'enable_vapor_uploads' => env('ENABLE_MEDIA_LIBRARY_VAPOR_UPLOADS', false),
    'vapor_route_prefix' => 'media-vapor',
    'vapor_route_middleware' => ['web', 'auth'],
];
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `MEDIA_DISK` | `public` | Default storage disk |
| `MEDIA_QUEUE` | `''` | Queue name |
| `QUEUE_CONNECTION` | `sync` | Queue connection |
| `QUEUE_CONVERSIONS_BY_DEFAULT` | `true` | Queue conversions by default |
| `QUEUE_CONVERSIONS_AFTER_DB_COMMIT` | `true` | Run after database commit |
| `IMAGE_DRIVER` | `gd` | Image processing driver |
| `FFMPEG_PATH` | `/usr/bin/ffmpeg` | FFMPEG binary path |
| `FFPROBE_PATH` | `/usr/bin/ffprobe` | FFProbe binary path |
| `FFMPEG_TIMEOUT` | `900` | FFMPEG timeout (seconds) |
| `FFMPEG_THREADS` | `0` | FFMPEG thread count |
| `MEDIA_DOWNLOADER_SSL` | `true` | SSL verification for downloads |
| `MEDIA_TEMPORARY_URL_DEFAULT_LIFETIME` | `5` | Temporary URL lifetime (minutes) |
| `MEDIA_PREFIX` | `''` | Storage path prefix |
| `FORCE_MEDIA_LIBRARY_LAZY_LOADING` | `true` | Force lazy loading |
| `ENABLE_MEDIA_LIBRARY_VAPOR_UPLOADS` | `false` | Enable Vapor upload routes |

## Advanced Usage

### Custom Path Generator

Create a custom path generator:

```php
namespace App\Support;

use Jegex\Media\Support\PathGenerator\DefaultPathGenerator;
use Jegex\Media\MediaCollections\Models\Media;

class CustomPathGenerator extends DefaultPathGenerator
{
    public function getPath(Media $media): string
    {
        return $media->model_type.'/'.date('Y/m/d').'/'.$media->id;
    }
}
```

Register it in config:

```php
'path_generator' => App\Support\CustomPathGenerator::class,
```

Or per-model:

```php
'custom_path_generators' => [
    App\Models\Post::class => App\Support\PostPathGenerator::class,
],
```

### Media Lifecycle Events

The `MediaObserver` handles:

- **creating**: Sets highest order number
- **created**: Dispatches conversion and responsive image jobs
- **updating**: Handles file renaming (if `moves_media_on_update` is true)
- **deleting**: Removes all associated files from disk

### Blade Component

```blade
{{-- Basic usage --}}
<x-media :media="$media" />

{{-- With custom class and loading --}}
<x-media :media="$media" class="w-full rounded-lg" loading="lazy" />

{{-- With conversion --}}
<x-media :media="$media" conversion="thumb" alt="Thumbnail" />

{{-- Override loading attribute --}}
<x-media :media="$media" :loading="null" />
```

## Testing

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

Run a specific test:

```bash
vendor/bin/pest --filter="test name"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

# AGENTS.md

## Package Identity
- **Name:** `jegex/laravel-media` — Laravel media library package
- **Namespace:** `Jegex\Media`
- **Based on:** `spatie/laravel-package-tools`

## Prerequisites
- PHP ^8.4
- Composer installed
- No `.env` needed — tested via Orchestra Testbench

## Commands (run from repo root)
- `composer install` — install deps (runs `testbench package:discover` post-install)
- `composer test` — run Pest tests
- `composer analyse` — run PHPStan (level 5 on src/, config/, database/)
- `composer format` — run Laravel Pint (auto-fix style)
- `composer test-coverage` — run Pest with coverage

> CI matrix: PHP 8.3/8.4/8.5 × Laravel 12/13 × ubuntu/windows. CI overrides `composer.json` constraints via `composer require` before testing.

## Architecture
```
src/
  LaravelMedia.php              # main class (stub — will be facade accessor)
  LaravelMediaServiceProvider   # extends Spatie PackageServiceProvider
  Commands/
    LaravelMediaCommand.php     # signature: `laravel-media`
  Facades/
    LaravelMedia.php            # facade accessor
  MediaCollections/
    Models/
      Media.php                 # Eloquent model (implements migration schema)
      Concerns/
        HasMedia.php            # trait to add media support to models
      Observers/
        MediaObserver.php       # lifecycle events observer
    FileAdder/
      FileAdder.php             # handle upload & attach media
  Conversions/
    ImageGenerators/
      ImageGenerator.php        # abstract base class
      GenericImage.php          # jpeg/png/gif handler
      Webp.php, Avif.php, Pdf.php, Svg.php, Video.php
    FileManipulator.php         # image manipulation with spatie/image
    Conversion.php              # conversion definition class
    Jobs/
      PerformConversionsJob.php # queue job for conversions
  ResponsiveImages/
    Jobs/
      GenerateResponsiveImagesJob.php
    TinyPlaceholderGenerator/
      Blurred.php               # generates tiny blurred placeholders
    WidthCalculator/
      FileSizeOptimizedWidthCalculator.php
  Downloaders/
    DefaultDownloader.php       # download media from URLs
  Support/
    FileNamer/
      DefaultFileNamer.php      # file naming strategy
    PathGenerator/
      DefaultPathGenerator.php  # storage path strategy
    FileRemover/
      DefaultFileRemover.php    # file removal strategy
    UrlGenerator/
      DefaultUrlGenerator.php   # URL generation strategy
    File.php                    # file helper utilities
config/
  media.php                     # full config (all classes active, Jegex\Media\* namespace)
database/
  migrations/                   # create_laravel_media_table
  factories/
tests/
  Pest.php                      # binds TestCase to all tests
  TestCase.php                  # extends Orchestra\TestCase, auto-registers provider
  ArchTest.php                  # pest-plugin-arch: no dd/dump/ray
```

## Key Dependencies
- **Runtime:** `spatie/laravel-package-tools` ^1.16, `spatie/image` ^3.3.2, `spatie/image-optimizer` ^1.8, `spatie/temporary-directory` ^2.2
- **Dev:** Pest ^4.0, Larastan ^3.0, Orchestra Testbench ^9/^10, Laravel Pint ^1.14
- **Supports:** Laravel 11, 12, 13 (`illuminate/contracts`)

## Testing
- Framework: Pest 4 + Orchestra Testbench
- Single test: `vendor/bin/pest --filter="test name"`
- Random order, fail on warnings/risky/empty suites
- Migration auto-run **enabled** in `TestCase::getEnvironmentSetUp()`
- Factory convention: `Jegex\Media\Database\Factories\{Model}Factory`
- 41 tests passing (Media, HasMedia, MediaObserver, Conversions, ResponsiveImages, Support, Downloaders)

## PHPStan
- Level 5, scans `src`, `config`, `database`
- Baseline: `phpstan-baseline.neon`
- Temp dir: `build/phpstan`

## Code Style
- Laravel Pint (default preset). Run `composer format` before commit.

## Notable Config Defaults (`config/media.php`)
- Media disk: `public` (env: `MEDIA_DISK`)
- Max file size: 10MB
- Queue: sync default (env: `QUEUE_CONNECTION`)
- Image driver: `gd` (env: `IMAGE_DRIVER`)
- FFMPEG: `/usr/bin/ffmpeg`, `/usr/bin/ffprobe`
- Config references `Jegex\Media\*` classes (all uncommented, classes created)

## Gotchas
- `LaravelMedia` class is a stub — will be facade accessor, most logic in other classes
- Namespace decided: `Jegex\Media\*` (shorter, matches config)
- `composer.json` requires PHP ^8.4 but CI tests 8.3 via matrix override
- Migration had syntax error (`} else` → `} elseif`) — sudah diperbaiki
- Kolom baru di migration: `alt_txt`, `caption`, `description` (TEXT/JSON nullable)
- JSON columns di migration menggunakan default `'[]'` untuk menghindari SQLite NOT NULL constraint

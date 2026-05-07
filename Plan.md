# PRD — Laravel Media Library (jegex/laravel-media)

## 1. Overview
Laravel package untuk manajemen media (upload, konversi gambar, responsive images, video thumbnails) yang terinspirasi dari `spatie/laravel-medialibrary`. Package ini masih dalam tahap skeleton.

## 2. Database Schema

### Tabel `media` (create_laravel_media_table)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT UNSIGNED (PK) | Primary key auto-increment |
| `model_type` | VARCHAR | Morph ke model pemilik media |
| `model_id` | UNSIGNED BIGINT | ID model pemilik |
| `uuid` | UUID (nullable, unique) | Unique identifier opsional |
| `collection_name` | STRING | Nama koleksi media |
| `name` | STRING / JSON | Nama file (JSON jika translatable) |
| `alt_txt` | TEXT / JSON (nullable) | Teks alternatif untuk aksesibilitas |
| `caption` | TEXT / JSON (nullable) | Caption/keterangan singkat media |
| `description` | TEXT / JSON (nullable) | Deskripsi lengkap media |
| `file_name` | STRING | Nama file fisik di disk |
| `mime_type` | STRING (nullable) | MIME type file |
| `disk` | STRING | Nama disk penyimpanan utama |
| `conversions_disk` | STRING (nullable) | Disk khusus untuk hasil konversi |
| `size` | UNSIGNED BIGINT | Ukuran file dalam bytes |
| `manipulations` | JSON | Data manipulasi yang akan diterapkan |
| `custom_properties` | JSON | Properti kustom dari developer |
| `generated_conversions` | JSON | Status konversi yang sudah dihasilkan |
| `responsive_images` | JSON | Daftar responsive image variations |
| `order_column` | UNSIGNED INT (nullable, indexed) | Kolom untuk sorting manual |
| `created_at / updated_at` | TIMESTAMP | Timestamps |

### Tabel `media_translations` (kondisional — hanya jika `translatable = 'astrotomic'`)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT UNSIGNED (PK) | Primary key |
| `media_id` | INT (unsigned, FK → media.id) | Relasi ke tabel media |
| `locale` | STRING (indexed) | Locale bahasa |
| `name` | STRING | Nama terjemahan |
| `alt_txt` | TEXT (nullable) | Teks alternatif terjemahan |
| `caption` | TEXT (nullable) | Caption terjemahan |
| `description` | TEXT (nullable) | Deskripsi terjemahan |
| unique: `[media_id, locale]` | | |

### Translatable Support (3 mode)
- `false` — kolom `name`, `alt_txt`, `caption`, `description` sebagai STRING/TEXT biasa (default)
- `'spatie'` — kolom `name`, `alt_txt`, `caption`, `description` sebagai JSON (pakai spatie/laravel-translation)
- `'astrotomic'` — tabel terpisah `media_translations` dengan kolom `name`, `alt_txt`, `caption`, `description` sebagai TEXT (pakai astrotomic/laravel-translatable)

## 3. Fitur Berdasarkan Config

### 3.1 Media Upload & Storage
- **Disk penyimpanan**: configurable via `disk_name` (default: `public`, env: `MEDIA_DISK`)
- **Max file size**: 10MB default, configurable via `max_file_size`
- **Prefix path**: opsional via `prefix` (env: `MEDIA_PREFIX`)
- **Remote upload headers**: S3 support (`CacheControl` default)
- **Vapor uploads**: enable route untuk Media Library Pro Vue/React components

### 3.2 Image Conversions
- **Image generators**: Image, Webp, Avif, Pdf, Svg, Video
- **Image optimizers**: Jpegoptim, Pngquant, Optipng, Svgo, Gifsicle, Cwebp, Avifenc
- **Image driver**: gd / imagick / vips (env: `IMAGE_DRIVER`, default: gd)
- **Temporary directory**: untuk proses konversi (default: `storage_path('media-library/temp')`)
- **Moves media on update**: opsional, butuh custom PathGenerator
- **Version URLs**: attach `?v=xx` query string ke URL

### 3.3 Video Thumbnails
- **FFMPEG binary path**: env: `FFMPEG_PATH` (default: `/usr/bin/ffmpeg`)
- **FFProbe binary path**: env: `FFPROBE_PATH` (default: `/usr/bin/ffprobe`)
- **Timeout**: env: `FFMPEG_TIMEOUT` (default: 900 detik)
- **Threads**: env: `FFMPEG_THREADS` (default: 0 = auto)

### 3.4 Queue System
- **Queue connection**: env: `QUEUE_CONNECTION` (default: sync)
- **Queue name**: env: `MEDIA_QUEUE` (default: kosong)
- **Conversions by default**: env: `QUEUE_CONVERSIONS_BY_DEFAULT` (default: true)
- **After database commit**: env: `QUEUE_CONVERSIONS_AFTER_DB_COMMIT` (default: true)
- **Jobs**: `PerformConversionsJob`, `GenerateResponsiveImagesJob`

### 3.5 Responsive Images
- **Width calculator**: `FileSizeOptimizedWidthCalculator` (30% lebih kecil per variasi)
- **Tiny placeholders**: enabled default, pakai `Blurred` (blurred jpg mini)
- **Use tiny placeholders**: toggle on/off

### 3.6 File Management
- **File namer**: `DefaultFileNamer` — strategi penamaan file hasil
- **Path generator**: `DefaultPathGenerator` — strategi path penyimpanan
- **Custom path generators**: mapping per model
- **File remover**: `DefaultFileRemover` — strategi penghapusan file
- **URL generator**: `DefaultUrlGenerator` — strategi generate URL
- **Media downloader**: `DefaultDownloader` — download dari URL (support SSL toggle)
- **Downloader SSL**: env: `MEDIA_DOWNLOADER_SSL` (default: true)

### 3.7 Media Model & Observer
- **Media model**: `Jegex\Media\MediaCollections\Models\Media` (belum diimplementasi)
- **Media observer**: `Jegex\Media\MediaCollections\Models\Observers\MediaObserver` (belum diimplementasi)

### 3.8 Miscellaneous
- **Temporary URL lifetime**: env: `MEDIA_TEMPORARY_URL_DEFAULT_LIFETIME` (default: 5 menit)
- **Default loading attribute**: `null` (bisa 'lazy', 'eager', 'auto')
- **Force lazy loading**: env: `FORCE_MEDIA_LIBRARY_LAZY_LOADING` (default: true)

## 4. Arsitektur yang Perlu Dibangun

### 4.1 Core Classes (belum ada — semua kelas di config menggunakan namespace `Jegex\Media\*`)

```
src/
├── MediaCollections/
│   ├── Models/
│   │   ├── Media.php                    # Eloquent model (sesuai schema migration)
│   │   │                               # Kolom: name, alt_txt, caption, description
│   │   │                               # (STRING/TEXT atau JSON tergantung mode translatable)
│   │   └── Observers/
│   │       └── MediaObserver.php        # Observer untuk lifecycle events
│   └── FileAdder/
│       └── FileAdder.php                # Handle upload & attach media
├── Conversions/
│   ├── ImageGenerators/
│   │   ├── ImageGenerator.php           # Base class
│   │   ├── Image.php
│   │   ├── Webp.php
│   │   ├── Avif.php
│   │   ├── Pdf.php
│   │   ├── Svg.php
│   │   └── Video.php
│   └── Jobs/
│       └── PerformConversionsJob.php    # Queue job untuk konversi
├── ResponsiveImages/
│   ├── Jobs/
│   │   └── GenerateResponsiveImagesJob.php
│   ├── TinyPlaceholderGenerator/
│   │   ├── TinyPlaceholderGenerator.php # Interface/base
│   │   └── Blurred.php
│   └── WidthCalculator/
│       ├── WidthCalculator.php          # Interface/base
│       └── FileSizeOptimizedWidthCalculator.php
├── Downloaders/
│   ├── Downloader.php                   # Interface/base
│   └── DefaultDownloader.php
├── Support/
│   ├── FileNamer/
│   │   ├── FileNamer.php               # Interface/base
│   │   └── DefaultFileNamer.php
│   ├── PathGenerator/
│   │   ├── PathGenerator.php           # Interface/base
│   │   └── DefaultPathGenerator.php
│   ├── FileRemover/
│   │   ├── FileRemover.php             # Interface/base
│   │   └── DefaultFileRemover.php
│   └── UrlGenerator/
│       ├── UrlGenerator.php            # Interface/base
│       └── DefaultUrlGenerator.php
├── LaravelMedia.php                    # Facade accessor (sudah ada, masih stub)
├── LaravelMediaServiceProvider.php     # Sudah ada
├── Commands/
│   └── LaravelMediaCommand.php         # Sudah ada (stub)
└── Facades/
    └── LaravelMedia.php                # Sudah ada
```

### 4.2 Namespace Decision
**Diputuskan:** Menggunakan `Jegex\Media\*` (sesuai config, lebih pendek)

Semua file sudah diupdate:
- `composer.json` autoload dan autoload-dev
- `composer.json` extra.laravel providers/aliases
- `config/media.php` - semua use statements uncommented
- Semua file di `src/` dan `tests/`

### 4.3 Bug pada Migration
Migration `create_media_table.php.stub` baris 23 memiliki syntax error:
```php
} else (config('media.translatable') === 'spatie') {
```
Seharusnya menggunakan `elseif`:
```php
} elseif (config('media.translatable') === 'spatie') {
```
Ini perlu diperbaiki sebelum migration bisa dijalankan.

## 5. Dependency Tambahan yang Diperlukan
Berdasarkan config, package memerlukan:
- `spatie/image-optimizer` — untuk optimizers (Jpegoptim, Pngquant, dll)
- `php-ffmpeg/php-ffmpeg` — opsional, untuk video thumbnails
- `spatie/laravel-translation` — opsional, untuk translatable mode 'spatie'
- `astrotomic/laravel-translatable` — opsional, untuk translatable mode 'astrotomic'

## 6. Environment Variables
| Variable | Default | Kegunaan |
|----------|---------|----------|
| `MEDIA_DISK` | `public` | Default storage disk |
| `MEDIA_QUEUE` | `''` | Queue name untuk konversi |
| `QUEUE_CONNECTION` | `sync` | Queue connection |
| `QUEUE_CONVERSIONS_BY_DEFAULT` | `true` | Default queue untuk konversi |
| `QUEUE_CONVERSIONS_AFTER_DB_COMMIT` | `true` | Run setelah commit |
| `IMAGE_DRIVER` | `gd` | Image processing driver |
| `FFMPEG_PATH` | `/usr/bin/ffmpeg` | Path binary FFMPEG |
| `FFPROBE_PATH` | `/usr/bin/ffprobe` | Path binary FFProbe |
| `FFMPEG_TIMEOUT` | `900` | Timeout FFMPEG (detik) |
| `FFMPEG_THREADS` | `0` | Thread count FFMPEG |
| `MEDIA_DOWNLOADER_SSL` | `true` | SSL verification download |
| `MEDIA_TEMPORARY_URL_DEFAULT_LIFETIME` | `5` | Lifetime temporary URL (menit) |
| `ENABLE_MEDIA_LIBRARY_VAPOR_UPLOADS` | `false` | Enable Vapor uploads |
| `MEDIA_PREFIX` | `''` | Prefix path media |
| `FORCE_MEDIA_LIBRARY_LAZY_LOADING` | `true` | Force lazy loading |

## 7. Rencana Implementasi (Prioritas)

### Phase 1 — Foundation
1. [x] Perbaiki syntax error di migration (`} else` → `} elseif` pada baris 23)
2. [x] Putuskan namespace → `Jegex\Media\*` (sesuai config)
3. [x] Buat `Media` model sesuai schema migration (termasuk kolom `alt_txt`, `caption`, `description`)
4. [x] Buat `MediaObserver`
5. [x] Buat `FileAdder` untuk upload & attach media
6. [x] Buat `HasMedia` trait
6. [x] Implementasi `DefaultPathGenerator`, `DefaultFileNamer`, `DefaultUrlGenerator`
7. [x] Implementasi `DefaultFileRemover`

### Phase 2 — Conversions
8. [x] Buat `ImageGenerator` base class + 6 generator (GenericImage, Webp, Avif, Pdf, Svg, Video)
9. [x] Integrasi `spatie/image` untuk manipulasi gambar (FileManipulator, Conversion)
10. [x] Setup image optimizers integration
11. [x] Buat `PerformConversionsJob`

### Phase 3 — Responsive Images & Queue
12. [x] Buat `GenerateResponsiveImagesJob`
13. [x] Implementasi `FileSizeOptimizedWidthCalculator`
14. [x] Implementasi `Blurred` placeholder generator
15. [x] Setup queue system integration (konfigurasi sudah ada, MediaObserver dispatches jobs)

### Phase 4 — Extras
16. [x] Buat `DefaultDownloader` (download dari URL dengan SSL config)
17. [x] Implementasi `DefaultFileRemover`
18. [x] Support translatable (spatie mode: JSON columns + getTranslation/setTranslation methods; astrotomic mode: MediaTranslation model + translations relationship)
19. [x] Vapor uploads route (VaporUploadsController: store, markAsFinished, getUploadParameters)
20. [x] Temporary URL support (getTemporaryUrl dengan default lifetime dari config, getUrlForConversion)
21. [x] Lazy loading support (toHtml(), __toString(), Blade component x-media dengan loading attribute)

## 8. Status Saat Ini (Semua Phase Selesai - Ready for Testing)

### Yang Sudah Dibuat (Complete Implementation)
- [x] Migration syntax error diperbaiki
- [x] Namespace diubah ke `Jegex\Media\*` di semua file
- [x] `Media` model dengan semua kolom (`name`, `alt_txt`, `caption`, `description`)
- [x] `Media` standalone creation methods: `createFromFile()`, `createFromString()`, `createFromBase64()`, `createFromUrl()` — tanpa perlu model terkait
- [x] `MediaTranslation` model untuk astrotomic translatable support
- [x] `MediaObserver` untuk lifecycle events (auto order column, dispatches conversion/responsive jobs on created, handles file rename on update, removes files on delete)
- [x] `HasMedia` trait untuk menambahkan media functionality ke model
- [x] `FileAdder` untuk upload & attach media (dari file, string, URL)
- [x] `DefaultPathGenerator`, `DefaultFileNamer`, `DefaultUrlGenerator`, `DefaultFileRemover`
- [x] `FileManipulator` + `Conversion` class untuk image manipulation
- [x] `ConversionCollection` untuk filtering queued/non-queued conversions
- [x] Semua ImageGenerator implementations (GenericImage, Webp, Avif, Pdf, Svg, Video)
- [x] Job implementations (PerformConversionsJob, GenerateResponsiveImagesJob) dengan queue connection/name config
- [x] Queue integration: MediaObserver dispatches jobs respecting queue_connection_name, queue_name, queue_conversions_after_database_commit
- [x] Queue integration tests (8 tests covering dispatch triggers, non-image filtering, queue config)
- [x] Translatable support: spatie mode (JSON columns + getTranslation/setTranslation) dan astrotomic mode (MediaTranslation model)
- [x] `VaporUploadsController` untuk Laravel Vapor uploads (store, markAsFinished, getUploadParameters routes)
- [x] Temporary URL: `getTemporaryUrl()` dengan default lifetime dari config, `getUrlForConversion()` untuk conversion URLs
- [x] Lazy loading: `toHtml()` dan `__toString()` pada Media model, Blade component `x-media` dengan loading attribute
- [x] `DefaultDownloader` (download dari URL dengan SSL toggle)
- [x] `FileSizeOptimizedWidthCalculator` (responsive width calculation)
- [x] `Blurred` placeholder generator (base64 tiny blurred images)
- [x] `File` support class untuk file utilities
- [x] `MediaFactory` untuk test fixtures
- [x] PHPStan configuration diperbaiki (ignore env() warnings, trait unused)
- [x] `composer.json` updated: namespace + spatie/image-optimizer dependency
- [x] `config/media.php` - semua use statements uncommented + vapor uploads config
- [x] Migration JSON columns default ke '[]' untuk menghindari NOT NULL constraint
- [x] Tests: 65 tests passing
- [x] PHPStan level 5: No errors
- [x] Documentation (README.md)

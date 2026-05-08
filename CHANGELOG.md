# Changelog

All notable changes to `laravel-media` will be documented in this file.

## v1.0.0-alpha.3 - 2026-05-08

### Removed
- **Translatable support**: Removed entire translatable feature (config `translatable`, `MediaTranslation` model, `setTranslation`/`getTranslation`/`getTranslatableValue`/`getAstrotomicValue` methods, migration conditional columns and `media_translations` table, `suggest` packages in composer.json, README section)
- **Dependencies**: Removed `spatie/laravel-translation` and `astrotomic/laravel-translatable` from suggest

### Changed
- **Migration**: Simplified to always create string columns directly (no conditional logic)
- **Media model**: Getters (`getName`, `getAltTxt`, `getCaption`, `getDescription`) simplified to return values directly

## v1.0.0-alpha.2 - 2026-05-08

### Fixed
- **Migration**: JSON columns (`manipulations`, `custom_properties`, `generated_conversions`, `responsive_images`) changed from `->default('[]')` to `->nullable()` to fix SQLite NOT NULL constraint issues
- **Migration**: `media_translations.media_id` refactored to use `foreignId()->constrained()->cascadeOnDelete()` (cleaner, idiomatic Laravel)

## v1.0.0-alpha.1 - 2026-05-08

### Added
- **Foundation**: Media Eloquent model, HasMedia trait, FileAdder for upload & attach, MediaObserver for lifecycle events
- **Storage**: DefaultPathGenerator, DefaultFileNamer, DefaultUrlGenerator, DefaultFileRemover
- **Image Conversions**: ImageGenerator base class + 6 generators (GenericImage, Webp, Avif, Pdf, Svg, Video), FileManipulator with spatie/image, Conversion definition class, PerformConversionsJob
- **Responsive Images**: FileSizeOptimizedWidthCalculator, Blurred tiny placeholder generator, GenerateResponsiveImagesJob
- **Queue Integration**: Configurable queue connection/name, per-conversion queued/nonQueued toggle, after-database-commit support
- **Translatable Support**: 3 modes — disabled (string columns), spatie (JSON columns), astrotomic (media_translations table)
- **Vapor Uploads**: VaporUploadsController with store, markAsFinished, getUploadParameters routes
- **ZIP Export**: Media collection ZIP download via maennchen/zipstream-php, supports conversion filtering
- **Lazy Loading**: toHtml(), __toString() on Media model, Blade x-media component with configurable loading attribute
- **Facade**: LaravelMedia facade for standalone media operations (createFromFile, createFromString, createFromBase64, createFromUrl, deleteMedia, moveMedia, copyMediaToDisk)
- **CLI**: `php artisan laravel-media` command
- **Testing**: 91 Pest tests (163 assertions), PHPStan level 5 clean, CI workflows (tests, phpstan, pint, changelog, dependabot)
- **Documentation**: Full README with installation, usage, configuration, and advanced usage guides

<?php

namespace Jegex\Media;

use Illuminate\Support\Facades\Route;
use Jegex\Media\Commands\LaravelMediaCommand;
use Jegex\Media\Http\Controllers\VaporUploadsController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMediaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-media')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_media_table')
            ->hasCommand(LaravelMediaCommand::class);
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        if (config('media.enable_vapor_uploads', false)) {
            $this->registerVaporRoutes();
        }
    }

    protected function registerVaporRoutes(): void
    {
        Route::prefix(config('media.vapor_route_prefix', 'media-vapor'))
            ->middleware(config('media.vapor_route_middleware', ['web', 'auth']))
            ->group(function () {
                Route::post('/', [VaporUploadsController::class, 'store'])
                    ->name('media-vapor.store');
                Route::post('/finished/{mediaId}', [VaporUploadsController::class, 'markAsFinished'])
                    ->name('media-vapor.finished');
                Route::post('/parameters', [VaporUploadsController::class, 'getUploadParameters'])
                    ->name('media-vapor.parameters');
            });
    }
}

<?php

namespace Jegex\Media\Commands;

use Illuminate\Console\Command;

class LaravelMediaCommand extends Command
{
    public $signature = 'laravel-media';

    public $description = 'Display media library statistics and info';

    public function handle(): int
    {
        $this->comment('Laravel Media v'.config('media.version', '1.0.0-alpha.1'));

        return self::SUCCESS;
    }
}

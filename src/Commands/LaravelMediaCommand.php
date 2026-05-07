<?php

namespace Jegex\LaravelMedia\Commands;

use Illuminate\Console\Command;

class LaravelMediaCommand extends Command
{
    public $signature = 'laravel-media';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}

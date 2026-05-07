<?php

namespace Jegex\Media\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jegex\Media\LaravelMedia
 */
class LaravelMedia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jegex\Media\LaravelMedia::class;
    }
}

<?php

namespace Jegex\LaravelMedia\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jegex\LaravelMedia\LaravelMedia
 */
class LaravelMedia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jegex\LaravelMedia\LaravelMedia::class;
    }
}

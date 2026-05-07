<?php

use Jegex\Media\Downloaders\DefaultDownloader;

it('can create downloader', function () {
    $downloader = new DefaultDownloader;

    expect($downloader)->toBeInstanceOf(DefaultDownloader::class);
});

it('throws exception for invalid url', function () {
    $downloader = new DefaultDownloader;

    $downloader->getTempFile('this-is-not-a-valid-url');
})->throws(RuntimeException::class);

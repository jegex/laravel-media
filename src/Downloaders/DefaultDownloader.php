<?php

namespace Jegex\Media\Downloaders;

class DefaultDownloader
{
    public function getTempFile(string $url): string
    {
        $temporaryDirectory = config('media.temporary_directory_path')
            ?? storage_path('media-library/temp');

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0755, true);
        }

        $tempFile = tempnam($temporaryDirectory, 'ml_');
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $tempFileWithExt = $tempFile.'.'.$extension;
        rename($tempFile, $tempFileWithExt);
        $tempFile = $tempFileWithExt;

        $verifySsl = config('media.media_downloader_ssl', true);

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Jegex/LaravelMedia Downloader',
            ],
            'ssl' => [
                'verify_peer' => $verifySsl,
                'verify_peer_name' => $verifySsl,
            ],
        ]);

        try {
            $content = @file_get_contents($url, false, $context);

            if ($content === false) {
                @unlink($tempFile);

                throw new \RuntimeException("Could not download file from: {$url}");
            }

            file_put_contents($tempFile, $content);

            return $tempFile;
        } catch (\Exception $e) {
            @unlink($tempFile);

            throw new \RuntimeException("Could not download file from: {$url}. {$e->getMessage()}");
        }
    }
}

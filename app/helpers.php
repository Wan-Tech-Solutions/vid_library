<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('array_last')) {
    /**
     * Get the last element from an array, optionally using a callback.
     */
    function array_last(array $array, ?callable $callback = null, $default = null)
    {
        if (empty($array)) {
            return value($default);
        }

        if (is_null($callback)) {
            return end($array);
        }

        foreach (array_reverse($array, true) as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }
}

if (! function_exists('media_url')) {
    /**
     * Resolve a media path to an accessible URL, handling storage and relative paths.
     */
    function media_url(?string $path, string $disk = 'public'): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = ltrim(Str::after($normalized, 'storage/'), '/');
        }

        try {
            $storage = Storage::disk($disk);

            if ($storage->exists($normalized)) {
                $url = $storage->url($normalized);

                return Str::startsWith($url, ['http://', 'https://']) ? $url : asset(ltrim($url, '/'));
            }
        } catch (\Throwable $e) {
            // Ignore and fall back to public path check.
        }

        $publicPath = public_path($normalized);

        if (file_exists($publicPath)) {
            return asset($normalized);
        }

        return null;
    }
}

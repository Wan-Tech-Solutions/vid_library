<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Allow chunked upload endpoints to bypass CSRF checks on shared hosting.
        'videos/uploads/chunk',
        'videos/uploads/cancel',
        'videos/uploads/status/*',
        // Finalize uploads coming from the JS uploader
        'videos',
    ];
}


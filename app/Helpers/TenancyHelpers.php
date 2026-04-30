<?php

use Illuminate\Support\Facades\Route;

/**
 * Ensure tenant is initialized
 *
 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
 */
function ensureTenant()
{
    if (! tenancy()->initialized) {
        abort(500, 'Tenant not initialized');
    }
}

/**
 * Ensure tenant is NOT initialized (for central routes)
 *
 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
 */
function ensureCentral()
{
    if (tenancy()->initialized) {
        abort(500, 'This route must be accessed in central context');
    }
}
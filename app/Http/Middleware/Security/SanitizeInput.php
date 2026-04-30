<?php

namespace App\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SanitizeInput
{
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'content',
        'description',
        'bio',
    ];

    public function handle(Request $request, Closure $next)
    {
        $this->sanitize($request);

        return $next($request);
    }

    protected function sanitize(Request $request): void
    {
        $all = $request->all();

        foreach ($all as $key => $value) {
            if ($this->shouldSanitize($key)) {
                $sanitized = $this->sanitizeValue($value);
                $request->request->set($key, $sanitized);
            }
        }
    }

    protected function shouldSanitize(string $key): bool
    {
        return !in_array($key, $this->except);
    }

    protected function sanitizeValue($value)
    {
        if (is_string($value)) {
            return $this->sanitizeString($value);
        }

        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        return $value;
    }

    protected function sanitizeString(string $value): string
    {
        $value = trim($value);
        $value = strip_tags($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return $value;
    }

    protected function sanitizeArray(array $value): array
    {
        foreach ($value as $key => $item) {
            $value[$key] = $this->sanitizeValue($item);
        }

        return $value;
    }
}
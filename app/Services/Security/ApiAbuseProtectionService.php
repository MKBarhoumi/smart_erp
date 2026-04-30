<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiAbuseProtectionService
{
    protected int $maxFailedAttempts = 5;
    protected int $blockDurationMinutes = 15;
    protected int $decayMinutes = 1;

    public function checkLoginAttempts(string $email, string $ip): bool
    {
        $key = "login_attempts:{$email}:{$ip}";
        $attempts = Cache::get($key, 0);

        if ($attempts >= $this->maxFailedAttempts) {
            $this->logBlockedAttempt($email, $ip, $attempts);
            return false;
        }

        return true;
    }

    public function recordFailedLogin(string $email, string $ip): void
    {
        $key = "login_attempts:{$email}:{$ip}";
        $attempts = Cache::increment($key, 1);
        Cache::put($key, $attempts, now()->addMinutes($this->decayMinutes));

        if ($attempts >= $this->maxFailedAttempts) {
            $this->blockIp($ip, $email);
        }

        Log::warning('Failed login attempt recorded', [
            'email' => $email,
            'ip' => $ip,
            'attempts' => $attempts,
        ]);
    }

    public function recordSuccessfulLogin(string $email, string $ip): void
    {
        $key = "login_attempts:{$email}:{$ip}";
        Cache::forget($key);

        Log::info('Successful login recorded', [
            'email' => $email,
            'ip' => $ip,
        ]);
    }

    public function isIpBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }

    public function blockIp(string $ip, string $reason = ''): void
    {
        Cache::put("blocked_ip:{$ip}", [
            'reason' => $reason,
            'blocked_at' => now()->toIso8601String(),
        ], now()->addMinutes($this->blockDurationMinutes));

        Log::critical('IP blocked due to abuse', [
            'ip' => $ip,
            'reason' => $reason,
            'duration_minutes' => $this->blockDurationMinutes,
        ]);
    }

    public function unblockIp(string $ip): void
    {
        Cache::forget("blocked_ip:{$ip}");
        Log::info('IP unblocked', ['ip' => $ip]);
    }

    public function getBlockedIps(): array
    {
        $blockedIps = [];
        $keys = Cache::get("blocked_ip:*");

        foreach ($keys as $key) {
            $ip = str_replace('blocked_ip:', '', $key);
            $blockedIps[$ip] = Cache::get($key);
        }

        return $blockedIps;
    }

    public function checkRateLimit(string $identifier, int $maxAttempts = 100, int $decayMinutes = 1): bool
    {
        $key = "rate_limit:{$identifier}";
        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        Cache::increment($key, 1);
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        return true;
    }

    protected function logBlockedAttempt(string $email, string $ip, int $attempts): void
    {
        Log::warning('Login attempt blocked due to too many failures', [
            'email' => $email,
            'ip' => $ip,
            'attempts' => $attempts,
            'max_attempts' => $this->maxFailedAttempts,
        ]);
    }

    public function setMaxFailedAttempts(int $maxAttempts): void
    {
        $this->maxFailedAttempts = $maxAttempts;
    }

    public function setBlockDurationMinutes(int $minutes): void
    {
        $this->blockDurationMinutes = $minutes;
    }
}
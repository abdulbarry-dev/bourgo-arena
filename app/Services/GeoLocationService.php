<?php

namespace App\Services;

use App\DTOs\GeoLocationDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    public function detect(Request $request): GeoLocationDTO
    {
        $ip = $request->ip();

        if ($this->isLocalIp($ip)) {
            return $this->localResult($ip);
        }

        $cacheKey = 'geo:'.$ip;
        $ttl = config('geo.cache_ttl_minutes', 1440);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return new GeoLocationDTO(
                countryCode: $cached['country_code'] ?? 'XX',
                countryName: $cached['country_name'] ?? 'Unknown',
                city: $cached['city'] ?? null,
                isp: $cached['isp'] ?? null,
                ip: $cached['ip'] ?? $ip,
            );
        }

        try {
            $response = Http::timeout(5)
                ->get('http://ip-api.com/json/'.$ip);

            if ($response->failed()) {
                throw new \RuntimeException('Geo-IP lookup failed with status '.$response->status());
            }

            $data = $response->json();

            $status = $data['status'] ?? 'fail';
            if ($status !== 'success') {
                throw new \RuntimeException('Geo-IP lookup returned: '.($data['message'] ?? 'unknown error'));
            }

            $dto = new GeoLocationDTO(
                countryCode: (string) ($data['countryCode'] ?? 'XX'),
                countryName: (string) ($data['country'] ?? 'Unknown'),
                city: $data['city'] ?? null,
                isp: $data['isp'] ?? null,
                ip: $ip,
            );

            Cache::put($cacheKey, $dto->toArray(), now()->addMinutes($ttl));

            return $dto;
        } catch (\Throwable $e) {
            Log::warning('GeoLocationService lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            if (config('geo.fail_closed', true)) {
                throw new \RuntimeException('Geo-IP service unavailable — payment blocked.');
            }

            return $this->localResult($ip);
        }
    }

    public function isTunisian(Request $request): bool
    {
        return $this->detect($request)->isTunisian();
    }

    private function isLocalIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) {
            return true;
        }

        return false;
    }

    private function localResult(string $ip): GeoLocationDTO
    {
        $isProd = app()->environment('production');

        return new GeoLocationDTO(
            countryCode: $isProd ? 'TN' : 'TN',
            countryName: $isProd ? 'Tunisia' : 'Tunisia',
            city: null,
            isp: null,
            ip: $ip,
        );
    }
}

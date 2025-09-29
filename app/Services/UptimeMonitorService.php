<?php

namespace App\Services;

use App\Models\Website;
use App\Models\WebsiteCheck;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UptimeMonitorService
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 10,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'UptimeMonitor/1.0'
            ]
        ]);
    }

    public function checkWebsite(Website $website): WebsiteCheck
    {
        $startTime = microtime(true);
        $checkTime = Carbon::now();

        try {
            // Use Laravel HTTP facade for better testing support
            $response = Http::timeout(10)->get($website->url);
            $responseTime = (int) round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();

            $isUp = $statusCode >= 200 && $statusCode < 400;

            $check = WebsiteCheck::create([
                'website_id' => $website->id,
                'is_up' => $isUp,
                'response_time_ms' => $responseTime,
                'status_code' => $statusCode,
                'error_message' => null,
                'checked_at' => $checkTime,
            ]);

            $this->updateWebsiteStatus($website, $isUp, $responseTime, $checkTime);

            Log::info("Website check completed", [
                'website_id' => $website->id,
                'url' => $website->url,
                'is_up' => $isUp,
                'response_time_ms' => $responseTime,
                'status_code' => $statusCode
            ]);

            return $check;

        } catch (\Exception $e) {
            $responseTime = (int) round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            $check = WebsiteCheck::create([
                'website_id' => $website->id,
                'is_up' => false,
                'response_time_ms' => $responseTime,
                'status_code' => null,
                'error_message' => $errorMessage,
                'checked_at' => $checkTime,
            ]);

            $this->updateWebsiteStatus($website, false, $responseTime, $checkTime);

            Log::warning("Website check failed", [
                'website_id' => $website->id,
                'url' => $website->url,
                'error' => $errorMessage,
                'response_time_ms' => $responseTime
            ]);

            return $check;
        }
    }

    private function updateWebsiteStatus(Website $website, bool $isUp, int $responseTime, Carbon $checkTime): void
    {
        $updateData = [
            'last_checked_at' => $checkTime,
            'response_time_ms' => $responseTime,
        ];

        if (!$isUp && $website->is_up) {
            $updateData['last_downtime_at'] = $checkTime;
        }

        $updateData['is_up'] = $isUp;

        $website->update($updateData);
    }
}
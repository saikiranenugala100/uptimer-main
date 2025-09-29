<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\UptimeMonitorService;
use App\Jobs\SendDowntimeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckWebsiteUptime implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public Website $website
    ) {}

    public function handle(UptimeMonitorService $monitorService): void
    {
        $wasUp = $this->website->is_up;

        $check = $monitorService->checkWebsite($this->website);

        if ($wasUp && !$check->is_up) {
            SendDowntimeNotification::dispatch($this->website, $check);

            Log::info("Website went down - notification queued", [
                'website_id' => $this->website->id,
                'url' => $this->website->url,
                'client_email' => $this->website->client->email
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Website check job failed", [
            'website_id' => $this->website->id,
            'url' => $this->website->url,
            'error' => $exception->getMessage()
        ]);
    }
}

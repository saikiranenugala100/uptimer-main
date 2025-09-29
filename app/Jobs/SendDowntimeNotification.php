<?php

namespace App\Jobs;

use App\Models\Website;
use App\Models\WebsiteCheck;
use App\Mail\WebsiteDownMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendDowntimeNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public Website $website,
        public WebsiteCheck $check
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->website->client->email)
                ->send(new WebsiteDownMail($this->website, $this->check));

            Log::info("Downtime notification sent", [
                'website_id' => $this->website->id,
                'url' => $this->website->url,
                'client_email' => $this->website->client->email,
                'check_id' => $this->check->id
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send downtime notification", [
                'website_id' => $this->website->id,
                'url' => $this->website->url,
                'client_email' => $this->website->client->email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Downtime notification job failed permanently", [
            'website_id' => $this->website->id,
            'url' => $this->website->url,
            'client_email' => $this->website->client->email,
            'error' => $exception->getMessage()
        ]);
    }
}

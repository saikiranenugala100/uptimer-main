<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Jobs\CheckWebsiteUptime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorWebsites extends Command
{
    protected $signature = 'monitor:websites';

    protected $description = 'Monitor all active websites for uptime';

    public function handle(): int
    {
        $websites = Website::with('client')
            ->where('is_active', true)
            ->whereHas('client', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        $this->info("Starting monitoring for {$websites->count()} websites...");

        foreach ($websites as $website) {
            CheckWebsiteUptime::dispatch($website);

            $this->line("Queued check for: {$website->url}");
        }

        Log::info("Website monitoring jobs queued", [
            'total_websites' => $websites->count()
        ]);

        $this->info("All monitoring jobs have been queued successfully.");

        return self::SUCCESS;
    }
}

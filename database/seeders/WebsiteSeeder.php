<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Website;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebsiteSeeder extends Seeder
{
    public function run(): void
    {
        $websiteData = [
            'monitoring@techcorp.com' => [
                ['url' => 'https://google.com', 'name' => 'Google Search'],
                ['url' => 'https://github.com', 'name' => 'GitHub'],
                ['url' => 'https://stackoverflow.com', 'name' => 'Stack Overflow'],
            ],
            'alerts@digitalagency.io' => [
                ['url' => 'https://laravel.com', 'name' => 'Laravel Framework'],
                ['url' => 'https://vuejs.org', 'name' => 'Vue.js'],
            ],
            'admin@ecomstore.com' => [
                ['url' => 'https://amazon.com', 'name' => 'Amazon'],
                ['url' => 'https://stripe.com', 'name' => 'Stripe'],
                ['url' => 'https://shopify.com', 'name' => 'Shopify'],
                ['url' => 'https://example.invalid', 'name' => 'Test Down Site'],
            ],
            'devops@startup.com' => [
                ['url' => 'https://redis.io', 'name' => 'Redis'],
                ['url' => 'https://httpbin.org/status/500', 'name' => 'Test 500 Error'],
            ],
            'monitoring@enterprise.com' => [
                ['url' => 'https://docker.com', 'name' => 'Docker'],
                ['url' => 'https://kubernetes.io', 'name' => 'Kubernetes'],
                ['url' => 'https://aws.amazon.com', 'name' => 'AWS'],
                ['url' => 'https://httpbin.org/status/404', 'name' => 'Test 404 Error'],
                ['url' => 'https://httpbin.org/delay/15', 'name' => 'Test Timeout'],
            ],
        ];

        foreach ($websiteData as $clientEmail => $websites) {
            $client = Client::where('email', $clientEmail)->first();

            if ($client) {
                foreach ($websites as $websiteInfo) {
                    Website::firstOrCreate(
                        [
                            'client_id' => $client->id,
                            'url' => $websiteInfo['url']
                        ],
                        [
                            'name' => $websiteInfo['name'],
                            'is_active' => true,
                            'is_up' => true,
                        ]
                    );
                }
            }
        }
    }
}

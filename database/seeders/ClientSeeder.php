<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'TechCorp Solutions - Enterprise Web Services',
                'email' => 'monitoring@techcorp.com',
                'is_active' => true,
            ],
            [
                'name' => 'Digital Creative Agency - Web Development',
                'email' => 'alerts@digitalagency.io',
                'is_active' => true,
            ],
            [
                'name' => 'Global E-commerce Platform',
                'email' => 'admin@ecomstore.com',
                'is_active' => true,
            ],
            [
                'name' => 'FinTech Startup - Cloud Infrastructure',
                'email' => 'devops@startup.com',
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise Corporation - Multi-Region Services',
                'email' => 'monitoring@enterprise.com',
                'is_active' => true,
            ],
        ];

        foreach ($clients as $clientData) {
            Client::firstOrCreate(
                ['email' => $clientData['email']],
                $clientData
            );
        }
    }
}

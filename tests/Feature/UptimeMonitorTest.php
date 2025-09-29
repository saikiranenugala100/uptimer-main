<?php

use App\Models\Client;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard displays clients and websites correctly', function () {
    // Create test data
    $client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'test@example.com',
        'is_active' => true
    ]);

    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'name' => 'Example Website',
        'is_active' => true,
        'is_up' => true
    ]);

    // Test dashboard loads
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('Dashboard')
             ->has('clients', 1)
             ->where('clients.0.email', 'test@example.com')
             ->where('clients.0.websites.0.url', 'https://example.com')
    );
});

test('api returns client websites correctly', function () {
    // Create test data
    $client = Client::factory()->create([
        'email' => 'test@example.com',
        'is_active' => true
    ]);

    $website1 = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example1.com',
        'is_active' => true
    ]);

    $website2 = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example2.com',
        'is_active' => true
    ]);

    // Test API endpoint
    $response = $this->get("/api/clients/{$client->id}/websites");

    $response->assertStatus(200);
    $response->assertJson([
        'websites' => [
            ['url' => 'https://example1.com'],
            ['url' => 'https://example2.com']
        ]
    ]);
});

test('only active clients and websites are displayed', function () {
    // Create active client with websites
    $activeClient = Client::factory()->create([
        'email' => 'active@example.com',
        'is_active' => true
    ]);

    $activeWebsite = Website::factory()->create([
        'client_id' => $activeClient->id,
        'url' => 'https://active.com',
        'is_active' => true
    ]);

    // Create inactive client and website
    $inactiveClient = Client::factory()->create([
        'email' => 'inactive@example.com',
        'is_active' => false
    ]);

    $inactiveWebsite = Website::factory()->create([
        'client_id' => $activeClient->id,
        'url' => 'https://inactive.com',
        'is_active' => false
    ]);

    // Test dashboard only shows active data
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('Dashboard')
             ->has('clients', 1)
             ->where('clients.0.email', 'active@example.com')
             ->has('clients.0.websites', 1)
             ->where('clients.0.websites.0.url', 'https://active.com')
    );
});

test('client websites api only returns active websites', function () {
    $client = Client::factory()->create(['is_active' => true]);

    $activeWebsite = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://active.com',
        'is_active' => true
    ]);

    $inactiveWebsite = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://inactive.com',
        'is_active' => false
    ]);

    $response = $this->get("/api/clients/{$client->id}/websites");

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'websites');
    $response->assertJsonPath('websites.0.url', 'https://active.com');
});

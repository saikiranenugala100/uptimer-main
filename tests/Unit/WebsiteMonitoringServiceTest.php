<?php

use App\Models\Client;
use App\Models\Website;
use App\Models\WebsiteCheck;
use App\Services\UptimeMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('monitors website successfully when site is up', function () {
    // Mock successful HTTP response
    Http::fake([
        'https://example.com' => Http::response('Success', 200)
    ]);

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'is_up' => false // Initially down
    ]);

    $service = new UptimeMonitorService();
    $check = $service->checkWebsite($website);

    // Assert check was created
    expect($check)->toBeInstanceOf(WebsiteCheck::class);
    expect($check->is_up)->toBeTrue();
    expect($check->status_code)->toBe(200);
    expect($check->response_time_ms)->toBeGreaterThan(0);
    expect($check->error_message)->toBeNull();

    // Assert website status was updated
    $website->refresh();
    expect($website->is_up)->toBeTrue();
    expect($website->last_checked_at)->not->toBeNull();
    expect($website->response_time_ms)->toBeGreaterThan(0);
});

test('monitors website when site is down', function () {
    // Mock failed HTTP response
    Http::fake([
        'https://example.com' => Http::response('Server Error', 500)
    ]);

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'is_up' => true // Initially up
    ]);

    $service = new UptimeMonitorService();
    $check = $service->checkWebsite($website);

    // Assert check was created
    expect($check)->toBeInstanceOf(WebsiteCheck::class);
    expect($check->is_up)->toBeFalse();
    expect($check->status_code)->toBe(500);
    expect($check->response_time_ms)->toBeGreaterThanOrEqual(0);

    // Assert website status was updated
    $website->refresh();
    expect($website->is_up)->toBeFalse();
    expect($website->last_checked_at)->not->toBeNull();
    expect($website->last_downtime_at)->not->toBeNull();
});

test('handles network timeout correctly', function () {
    // Mock network timeout
    Http::fake([
        'https://example.com' => function () {
            throw new \GuzzleHttp\Exception\ConnectException(
                'Connection timeout',
                new \GuzzleHttp\Psr7\Request('GET', 'https://example.com')
            );
        }
    ]);

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'is_up' => true
    ]);

    $service = new UptimeMonitorService();
    $check = $service->checkWebsite($website);

    // Assert check recorded failure
    expect($check->is_up)->toBeFalse();
    expect($check->error_message)->toContain('Connection timeout');
    expect($check->status_code)->toBeNull();

    // Assert website marked as down
    $website->refresh();
    expect($website->is_up)->toBeFalse();
});

test('records response time accurately', function () {
    // Mock normal response (no delay in testing)
    Http::fake([
        'https://example.com' => Http::response('Success', 200)
    ]);

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com'
    ]);

    $service = new UptimeMonitorService();
    $check = $service->checkWebsite($website);

    // Response time should be recorded (can be 0 in mocked tests)
    expect($check->response_time_ms)->toBeGreaterThanOrEqual(0);
    expect($check->response_time_ms)->toBeLessThan(10000); // Less than 10 seconds
});

test('considers 4xx status codes as down', function () {
    // Mock 404 response
    Http::fake([
        'https://example.com' => Http::response('Not Found', 404)
    ]);

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com'
    ]);

    $service = new UptimeMonitorService();
    $check = $service->checkWebsite($website);

    expect($check->is_up)->toBeFalse();
    expect($check->status_code)->toBe(404);
});

test('considers 2xx status codes as up', function () {
    Http::fake([
        'https://example.com' => Http::response('Success', 200)
    ]);

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com'
    ]);

    $service = new UptimeMonitorService();
    $check = $service->checkWebsite($website);

    expect($check->is_up)->toBeTrue();
    expect($check->status_code)->toBe(200);
});

test('considers 3xx status codes as up', function () {
    Http::fake([
        'https://example.com' => Http::response('Redirect', 301)
    ]);

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com'
    ]);

    $service = new UptimeMonitorService();
    $check = $service->checkWebsite($website);

    expect($check->is_up)->toBeTrue();
    expect($check->status_code)->toBe(301);
});

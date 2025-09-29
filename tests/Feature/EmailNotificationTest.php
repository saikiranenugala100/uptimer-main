<?php

use App\Jobs\CheckWebsiteUptime;
use App\Jobs\SendDowntimeNotification;
use App\Mail\WebsiteDownMail;
use App\Models\Client;
use App\Models\Website;
use App\Models\WebsiteCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('sends downtime notification when website goes down', function () {
    Mail::fake();

    $client = Client::factory()->create([
        'email' => 'test@example.com'
    ]);

    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'is_up' => true // Initially up
    ]);

    $check = WebsiteCheck::factory()->create([
        'website_id' => $website->id,
        'is_up' => false, // Now down
        'status_code' => 500,
        'error_message' => 'Server Error'
    ]);

    // Dispatch the notification job
    $job = new SendDowntimeNotification($website, $check);
    $job->handle();

    // Assert email was queued (since our mail extends ShouldQueue)
    Mail::assertQueued(WebsiteDownMail::class, function ($mail) use ($client, $website) {
        return $mail->hasTo($client->email) &&
               $mail->website->id === $website->id;
    });
});

test('email has correct subject format', function () {
    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com'
    ]);

    $check = WebsiteCheck::factory()->create([
        'website_id' => $website->id,
        'is_up' => false
    ]);

    $mail = new WebsiteDownMail($website, $check);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe('https://example.com is down!');
});

test('email has correct sender', function () {
    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com'
    ]);

    $check = WebsiteCheck::factory()->create([
        'website_id' => $website->id,
        'is_up' => false
    ]);

    $mail = new WebsiteDownMail($website, $check);
    $envelope = $mail->envelope();

    expect($envelope->from->address)->toBe('do-not-reply@example.com');
    expect($envelope->from->name)->toBe('Uptime Monitor');
});

test('email contains website details', function () {
    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com'
    ]);

    $check = WebsiteCheck::factory()->create([
        'website_id' => $website->id,
        'is_up' => false,
        'status_code' => 404,
        'error_message' => 'Not Found',
        'response_time_ms' => 1500
    ]);

    $mail = new WebsiteDownMail($website, $check);
    $content = $mail->content();

    expect($content->with['website'])->toBe($website);
    expect($content->with['check'])->toBe($check);
});

test('monitoring job queues notification when website goes down', function () {
    Queue::fake();

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'is_up' => true // Initially up
    ]);

    // Mock the service to return a down check
    $this->mock(\App\Services\UptimeMonitorService::class, function ($mock) use ($website) {
        $check = WebsiteCheck::factory()->make([
            'website_id' => $website->id,
            'is_up' => false
        ]);
        $mock->shouldReceive('checkWebsite')
             ->with($website)
             ->andReturn($check);
    });

    // Execute the job
    $job = new CheckWebsiteUptime($website);
    $job->handle(app(\App\Services\UptimeMonitorService::class));

    // Assert notification was queued
    Queue::assertPushed(SendDowntimeNotification::class, function ($job) use ($website) {
        return $job->website->id === $website->id;
    });
});

test('no notification sent when website stays up', function () {
    Queue::fake();

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'is_up' => true // Initially up
    ]);

    // Mock the service to return an up check
    $this->mock(\App\Services\UptimeMonitorService::class, function ($mock) use ($website) {
        $check = WebsiteCheck::factory()->make([
            'website_id' => $website->id,
            'is_up' => true // Still up
        ]);
        $mock->shouldReceive('checkWebsite')
             ->with($website)
             ->andReturn($check);
    });

    // Execute the job
    $job = new CheckWebsiteUptime($website);
    $job->handle(app(\App\Services\UptimeMonitorService::class));

    // Assert no notification was queued
    Queue::assertNotPushed(SendDowntimeNotification::class);
});

test('no notification sent when website stays down', function () {
    Queue::fake();

    $client = Client::factory()->create();
    $website = Website::factory()->create([
        'client_id' => $client->id,
        'url' => 'https://example.com',
        'is_up' => false // Initially down
    ]);

    // Mock the service to return a down check
    $this->mock(\App\Services\UptimeMonitorService::class, function ($mock) use ($website) {
        $check = WebsiteCheck::factory()->make([
            'website_id' => $website->id,
            'is_up' => false // Still down
        ]);
        $mock->shouldReceive('checkWebsite')
             ->with($website)
             ->andReturn($check);
    });

    // Execute the job
    $job = new CheckWebsiteUptime($website);
    $job->handle(app(\App\Services\UptimeMonitorService::class));

    // Assert no notification was queued
    Queue::assertNotPushed(SendDowntimeNotification::class);
});

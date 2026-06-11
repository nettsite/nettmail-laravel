<?php

use NettSite\NettMail\Console\Commands\PurgeRetentionCommand;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\Send;

it('purges sends and orphaned events older than the retention horizon', function (): void {
    config(['nettmail.retention.send_log_years' => 1]);

    $oldSend = Send::factory()->create(['created_at' => now()->subYears(2)]);
    Event::factory()->create(['send_id' => $oldSend->id, 'created_at' => now()->subYears(2)]);

    $recentSend = Send::factory()->create(['created_at' => now()->subMonths(1)]);
    Event::factory()->create(['send_id' => $recentSend->id, 'created_at' => now()->subMonths(1)]);

    $orphanedEvent = Event::factory()->create(['send_id' => null, 'created_at' => now()->subYears(2)]);

    $this->artisan(PurgeRetentionCommand::class)->assertSuccessful();

    expect(Send::query()->find($oldSend->id))->toBeNull();
    expect(Event::query()->where('send_id', $oldSend->id)->exists())->toBeFalse();
    expect(Event::query()->find($orphanedEvent->id))->toBeNull();

    expect(Send::query()->find($recentSend->id))->not->toBeNull();
    expect(Event::query()->where('send_id', $recentSend->id)->exists())->toBeTrue();
});

it('respects the configured retention horizon', function (): void {
    config(['nettmail.retention.send_log_years' => 5]);

    $send = Send::factory()->create(['created_at' => now()->subYears(2)]);

    $this->artisan(PurgeRetentionCommand::class)->assertSuccessful();

    expect(Send::query()->find($send->id))->not->toBeNull();
});

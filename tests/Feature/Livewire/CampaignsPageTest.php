<?php

use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use NettSite\NettMail\Jobs\ProcessCampaignSend;
use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\CampaignLink;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\Event;
use NettSite\NettMail\Models\MailingList;
use NettSite\NettMail\Models\Send;
use NettSite\NettMail\Models\Template;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function (): void {
    $this->actingAs(UserFactory::new()->create());
});

it('lists campaigns and creates a new one', function (): void {
    $list = MailingList::factory()->create(['name' => 'Newsletter']);
    $template = Template::factory()->create();

    Campaign::factory()->create(['list_id' => $list->id, 'template_id' => $template->id, 'name' => 'Existing campaign']);

    $this->get(route('nettmail.campaigns.index'))
        ->assertSuccessful()
        ->assertSee('Existing campaign');

    Livewire::test('nettmail::campaigns.index')
        ->set('showForm', true)
        ->set('name', 'New campaign')
        ->set('subject', 'Hello')
        ->set('listId', $list->id)
        ->set('templateId', $template->id)
        ->call('create')
        ->assertRedirect();

    expect(Campaign::query()->where('name', 'New campaign')->exists())->toBeTrue();
});

it('schedules, unschedules and sends a draft campaign', function (): void {
    Bus::fake();

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::Draft]);

    Livewire::test('nettmail::campaigns.show', ['campaign' => $campaign])
        ->set('scheduledAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('schedule');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Scheduled);

    Livewire::test('nettmail::campaigns.show', ['campaign' => $campaign])
        ->call('unschedule');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Draft);

    Livewire::test('nettmail::campaigns.show', ['campaign' => $campaign])
        ->call('sendNow');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sending);

    Bus::assertDispatched(ProcessCampaignSend::class);
});

it('pauses and resumes a sending campaign', function (): void {
    Bus::fake();

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::Sending]);

    Livewire::test('nettmail::campaigns.show', ['campaign' => $campaign])
        ->call('pause');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Paused);

    Livewire::test('nettmail::campaigns.show', ['campaign' => $campaign])
        ->call('resume');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sending);
});

it('shows campaign analytics with rates, top links and timeline', function (): void {
    $campaign = Campaign::factory()->create(['status' => CampaignStatus::Sent]);

    $contact = Contact::factory()->create();
    $send = Send::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'sent',
        'sent_at' => now(),
        'delivered_at' => now(),
        'opened_at' => now(),
        'clicked_at' => now(),
    ]);

    $link = CampaignLink::factory()->create(['campaign_id' => $campaign->id, 'link_hash' => 'abc123', 'url' => 'https://example.test']);

    Event::factory()->create([
        'send_id' => $send->id,
        'type' => EventType::Opened->value,
        'payload' => [],
    ]);

    Event::factory()->create([
        'send_id' => $send->id,
        'type' => EventType::Clicked->value,
        'payload' => ['link_hash' => $link->link_hash, 'url' => $link->url],
    ]);

    $component = Livewire::test('nettmail::campaigns.show', ['campaign' => $campaign]);

    expect($component->get('stats')['total'])->toBe(1)
        ->and($component->get('stats')['uniqueOpens'])->toBe(1)
        ->and($component->get('stats')['uniqueClicks'])->toBe(1)
        ->and($component->get('topLinks')[0]['url'])->toBe('https://example.test');
});

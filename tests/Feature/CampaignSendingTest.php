<?php

use Illuminate\Support\Facades\Queue;
use NettSite\NettMail\Campaigns\CampaignAudienceResolver;
use NettSite\NettMail\Campaigns\CampaignLifecycle;
use NettSite\NettMail\Console\Commands\DispatchScheduledCampaignsCommand;
use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Domain\Campaigns\CampaignStatus;
use Nettsite\NettMail\Core\Domain\Contacts\UnsubscribeTokenGenerator;
use Nettsite\NettMail\Core\NettMail as CoreNettMail;
use NettSite\NettMail\Jobs\ProcessCampaignSend;
use NettSite\NettMail\Jobs\SendCampaignEmail;
use NettSite\NettMail\Models\Campaign;
use NettSite\NettMail\Models\CampaignLink;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;
use NettSite\NettMail\Models\MailingList;
use NettSite\NettMail\Models\Send;
use NettSite\NettMail\Models\Template;
use NettSite\NettMail\Tests\Fakes\FakeMailDriver;

beforeEach(function (): void {
    config()->set('app.url', 'https://app.test');
    config()->set('nettmail.from.email', 'sender@example.test');
    config()->set('nettmail.from.name', 'Sender');

    $this->fakeDriver = new FakeMailDriver;
    $this->app->bind(MailDriverContract::class, fn () => $this->fakeDriver);
});

it('prepares the template, builds the audience, and dispatches sends', function (): void {
    Queue::fake();

    $list = MailingList::factory()->create();
    $template = Template::factory()->create([
        'html' => '<p><a href="https://example.com">Visit</a></p><p>{{unsubscribe_url}}</p>',
    ]);
    $campaign = Campaign::factory()->create([
        'list_id' => $list->id,
        'template_id' => $template->id,
        'status' => CampaignStatus::Sending,
    ]);

    $subscribed = Contact::factory()->create();
    ListContact::factory()->create(['list_id' => $list->id, 'contact_id' => $subscribed->id]);

    $unsubscribed = Contact::factory()->create(['global_unsubscribed_at' => now()]);
    ListContact::factory()->create(['list_id' => $list->id, 'contact_id' => $unsubscribed->id]);

    (new ProcessCampaignSend($campaign->id))->handle($this->app->make(CampaignAudienceResolver::class));

    $campaign->refresh();
    expect($campaign->prepared_html)->not->toBeNull();
    expect($campaign->prepared_html)->toContain('track/open/');
    expect($campaign->send_token_placeholder)->not->toBeNull();

    expect(CampaignLink::query()->where('campaign_id', $campaign->id)->count())->toBe(1);

    $sends = Send::query()->where('campaign_id', $campaign->id)->get();
    expect($sends)->toHaveCount(1);
    expect($sends->first()->contact_id)->toBe($subscribed->id);
    expect($sends->first()->status)->toBe('queued');

    Queue::assertPushed(SendCampaignEmail::class);
});

it('fails the campaign when the template is missing the unsubscribe link', function (): void {
    Queue::fake();

    $list = MailingList::factory()->create();
    $template = Template::factory()->create(['html' => '<p>No unsubscribe link here.</p>']);
    $campaign = Campaign::factory()->create([
        'list_id' => $list->id,
        'template_id' => $template->id,
        'status' => CampaignStatus::Sending,
    ]);

    (new ProcessCampaignSend($campaign->id))->handle($this->app->make(CampaignAudienceResolver::class));

    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Failed);

    Queue::assertNotPushed(SendCampaignEmail::class);
});

it('marks the campaign as sent once no sends are left queued', function (): void {
    Queue::fake();

    $list = MailingList::factory()->create();
    $template = Template::factory()->create();
    $campaign = Campaign::factory()->create([
        'list_id' => $list->id,
        'template_id' => $template->id,
        'status' => CampaignStatus::Sending,
    ]);

    $contact = Contact::factory()->create();
    ListContact::factory()->create(['list_id' => $list->id, 'contact_id' => $contact->id]);

    $audience = $this->app->make(CampaignAudienceResolver::class);
    (new ProcessCampaignSend($campaign->id))->handle($audience);

    Send::query()->where('campaign_id', $campaign->id)->update(['status' => 'sent']);

    (new ProcessCampaignSend($campaign->id))->handle($audience);

    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Sent);
    expect($campaign->sent_at)->not->toBeNull();
});

it('renders and sends a campaign email with merge tags and unsubscribe headers', function (): void {
    $list = MailingList::factory()->create();
    $template = Template::factory()->create([
        'html' => '<p>Hi {{first_name}}</p><p>{{unsubscribe_url}}</p>',
    ]);
    $campaign = Campaign::factory()->create([
        'list_id' => $list->id,
        'template_id' => $template->id,
        'status' => CampaignStatus::Sending,
    ]);

    $contact = Contact::factory()->create(['first_name' => 'Ada']);
    ListContact::factory()->create(['list_id' => $list->id, 'contact_id' => $contact->id]);

    (new ProcessCampaignSend($campaign->id))->handle($this->app->make(CampaignAudienceResolver::class));

    $send = Send::query()->where('campaign_id', $campaign->id)->where('contact_id', $contact->id)->firstOrFail();

    (new SendCampaignEmail($send->id))->handle(
        $this->app->make(CoreNettMail::class),
        $this->app->make(UnsubscribeTokenGenerator::class),
    );

    expect($this->fakeDriver->lastMessage)->not->toBeNull();
    expect($this->fakeDriver->lastMessage->html)->toContain('Hi Ada');
    expect($this->fakeDriver->lastMessage->html)->toContain('/nettmail/unsubscribe/');
    expect($this->fakeDriver->lastMessage->headers)->toHaveKey('List-Unsubscribe');
    expect($this->fakeDriver->lastMessage->headers)->toHaveKey('List-Unsubscribe-Post');

    $send->refresh();
    expect($send->status)->toBe('sent');
    expect($send->sent_at)->not->toBeNull();
    expect($send->provider_message_id)->toBe('fake-message-id@example.test');
});

it('marks suppressed contacts as suppressed without sending', function (): void {
    $list = MailingList::factory()->create();
    $template = Template::factory()->create();
    $campaign = Campaign::factory()->create([
        'list_id' => $list->id,
        'template_id' => $template->id,
        'status' => CampaignStatus::Sending,
        'prepared_html' => '<p>{{unsubscribe_url}}</p>',
        'prepared_text' => 'Unsubscribe: {{unsubscribe_url}}',
        'send_token_placeholder' => '{{__send_token_placeholder}}',
    ]);

    $contact = Contact::factory()->create(['global_unsubscribed_at' => now()]);

    $send = Send::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'queued',
    ]);

    (new SendCampaignEmail($send->id))->handle(
        $this->app->make(CoreNettMail::class),
        $this->app->make(UnsubscribeTokenGenerator::class),
    );

    expect($this->fakeDriver->lastMessage)->toBeNull();

    $send->refresh();
    expect($send->status)->toBe('suppressed');
});

it('pauses and resumes a campaign, redispatching queued sends', function (): void {
    Queue::fake();

    $list = MailingList::factory()->create();
    $template = Template::factory()->create();
    $campaign = Campaign::factory()->create([
        'list_id' => $list->id,
        'template_id' => $template->id,
        'status' => CampaignStatus::Sending,
    ]);

    $send = Send::factory()->create(['campaign_id' => $campaign->id, 'status' => 'queued']);

    $lifecycle = new CampaignLifecycle;

    $lifecycle->pause($campaign);
    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Paused);

    $lifecycle->resume($campaign);
    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Sending);

    Queue::assertPushed(SendCampaignEmail::class, fn (SendCampaignEmail $job) => true);
    Queue::assertPushed(ProcessCampaignSend::class);
});

it('dispatches due scheduled campaigns', function (): void {
    Queue::fake();

    $list = MailingList::factory()->create();
    $template = Template::factory()->create();
    $campaign = Campaign::factory()->create([
        'list_id' => $list->id,
        'template_id' => $template->id,
        'status' => CampaignStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);

    $this->artisan(DispatchScheduledCampaignsCommand::class)->assertSuccessful();

    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Sending);

    Queue::assertPushed(ProcessCampaignSend::class);
});

# NettMail Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nettsite/nettmail-laravel.svg?style=flat-square)](https://packagist.org/packages/nettsite/nettmail-laravel)
[![GitHub Tests Action Status](https://github.com/nettsite/nettmail-laravel/actions/workflows/run-tests.yml/badge.svg)](https://github.com/nettsite/nettmail-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/nettsite/nettmail-laravel/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/nettsite/nettmail-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nettsite/nettmail-laravel.svg?style=flat-square)](https://packagist.org/packages/nettsite/nettmail-laravel)

Laravel adapter for [`nettmail/core`](https://github.com/nettsite/nettmail-core) — Eloquent models, service provider, queued jobs, and Livewire admin UI for the NettMail email package.

## Installation

You can install the package via composer:

```bash
composer require nettsite/nettmail-laravel
```

Run the migrations (the package loads its own migrations — do not publish them, or they will run twice):

```bash
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="nettmail-config"
```

The config file lets you configure the mail driver (`php`, `smtp`, `resend`, `mailersend`, `mailgun`, `postmark`, `ses`), default sender identity, bounce mailbox, sending rate limit, retention period, and CAN-SPAM compliance address. See `config/nettmail.php` after publishing for the full list of options.

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="nettmail-views"
```

The template editor uses [GrapesJS](https://grapesjs.com) with the newsletter preset, bundled as a prebuilt asset — fully self-hosted, no external service or account required.

Publish the editor asset with:

```bash
php artisan vendor:publish --tag="nettmail-assets"
```

This copies `grapesjs-editor.js` and `grapesjs-editor.css` to `public/vendor/nettmail`. Re-run this command after upgrading the package to pick up editor updates.

The admin UI is mounted at `/nettmail` (configurable via `NETTMAIL_ROUTES_PREFIX`) behind the `web` and `auth` middleware (configurable via `routes.middleware`). It includes pages for the dashboard, contacts, lists, segments, campaigns, templates, and settings.

## Usage

```php
use NettSite\NettMail\Facades\NettMail;

NettMail::eraseContact($contactId);
```

## Scheduler

NettMail ships several artisan commands that should run on a schedule. Register them in your host app's `bootstrap/app.php`:

```php
use Illuminate\Console\Scheduling\Schedule;

->withSchedule(function (Schedule $schedule): void {
    // Sends scheduled broadcast campaigns once their send time arrives.
    $schedule->command('nettmail:dispatch-scheduled')->everyMinute();

    // Polls the configured bounce mailbox for DSNs and complaints.
    $schedule->command('nettmail:poll-bounces')->everyFiveMinutes();

    // Purges send logs and events older than `retention.send_log_years`.
    $schedule->command('nettmail:purge')->daily();
})
```

If you've registered a contact source (see below), keep contacts in sync on a schedule too:

```php
$schedule->command('nettmail:sync-contacts merlin')->hourly();
```

## Contact sources

NettMail keeps its own copy of contacts (`nettmail_contacts`) and syncs them from your host app via a `ContactSourceContract` implementation, registered with `ContactSourceRegistry`.

```php
use Nettsite\NettMail\Core\Contracts\ContactSourceContract;

class MerlinClientContactSource implements ContactSourceContract
{
    public function label(): string
    {
        return 'Merlin clients';
    }

    public function key(): string
    {
        return 'merlin';
    }

    /**
     * @return iterable<array{email: string, first_name?: string, last_name?: string, phone?: string, metadata?: array<string, mixed>, source_id?: string|int}>
     */
    public function contacts(): iterable
    {
        foreach (Client::query()->whereNotNull('email')->cursor() as $client) {
            yield [
                'email' => $client->email,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'phone' => $client->phone,
                'metadata' => ['client_id' => $client->id],
                'source_id' => $client->id,
            ];
        }
    }

    /**
     * @return array{email: string, first_name?: string, last_name?: string, phone?: string, metadata?: array<string, mixed>, source_id?: string|int}|null
     */
    public function findContact(string|int $sourceId): ?array
    {
        $client = Client::find($sourceId);

        if ($client === null) {
            return null;
        }

        return [
            'email' => $client->email,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'phone' => $client->phone,
            'metadata' => ['client_id' => $client->id],
            'source_id' => $client->id,
        ];
    }
}
```

Register it in a service provider's `boot()` method:

```php
use NettSite\NettMail\Contacts\ContactSourceRegistry;

public function boot(ContactSourceRegistry $registry): void
{
    $registry->register(new MerlinClientContactSource);
}
```

Then sync contacts on demand or via the scheduler:

```bash
php artisan nettmail:sync-contacts merlin
```

## Navigation

Add links to the NettMail admin pages in your host app's navigation. Routes are named `nettmail.*` and the configured nav group label is available via `config('nettmail.nav_group')`:

```blade
@if (Route::has('nettmail.dashboard'))
    <x-nav-group :label="config('nettmail.nav_group')">
        <x-nav-link :href="route('nettmail.dashboard')" wire:navigate>Dashboard</x-nav-link>
        <x-nav-link :href="route('nettmail.contacts.index')" wire:navigate>Contacts</x-nav-link>
        <x-nav-link :href="route('nettmail.lists.index')" wire:navigate>Lists</x-nav-link>
        <x-nav-link :href="route('nettmail.segments.index')" wire:navigate>Segments</x-nav-link>
        <x-nav-link :href="route('nettmail.campaigns.index')" wire:navigate>Campaigns</x-nav-link>
        <x-nav-link :href="route('nettmail.templates.index')" wire:navigate>Templates</x-nav-link>
        <x-nav-link :href="route('nettmail.settings')" wire:navigate>Settings</x-nav-link>
    </x-nav-group>
@endif
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nettsite](https://github.com/nettsite)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

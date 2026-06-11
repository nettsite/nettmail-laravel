<?php

namespace NettSite\NettMail;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Facades\Mail;
use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Drivers\MailersendDriver;
use Nettsite\NettMail\Core\Drivers\MailgunDriver;
use Nettsite\NettMail\Core\Drivers\PhpMailDriver;
use Nettsite\NettMail\Core\Drivers\PostmarkDriver;
use Nettsite\NettMail\Core\Drivers\ResendDriver;
use Nettsite\NettMail\Core\Drivers\SesDriver;
use Nettsite\NettMail\Core\Drivers\SmtpDriver;
use Nettsite\NettMail\Core\NettMail as CoreNettMail;
use NettSite\NettMail\Mail\NettMailTransport;
use NettSite\NettMail\Storage\EloquentAdapter;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NettMailServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('nettmail')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('webhooks')
            ->discoversMigrations()
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->bind(MailDriverContract::class, function (): MailDriverContract {
            return $this->createDriver();
        });

        $this->app->bind(StorageAdapterContract::class, EloquentAdapter::class);

        $this->app->singleton(CoreNettMail::class, function ($app): CoreNettMail {
            return new CoreNettMail(
                $app->make(MailDriverContract::class),
                $app->make(StorageAdapterContract::class),
            );
        });
    }

    public function packageBooted(): void
    {
        Mail::extend('nettmail', function (): NettMailTransport {
            return new NettMailTransport(
                $this->app->make(CoreNettMail::class),
                $this->app->make(StorageAdapterContract::class),
            );
        });
    }

    private function createDriver(): MailDriverContract
    {
        $driver = config('nettmail.driver');

        return match ($driver) {
            'php' => new PhpMailDriver(
                config('nettmail.drivers.php.command'),
            ),
            'smtp' => new SmtpDriver(
                config('nettmail.drivers.smtp.host'),
                (int) config('nettmail.drivers.smtp.port'),
                config('nettmail.drivers.smtp.username'),
                config('nettmail.drivers.smtp.password'),
                config('nettmail.drivers.smtp.encryption'),
            ),
            'resend' => new ResendDriver(
                config('nettmail.drivers.resend.api_key'),
                ...$this->httpClientArgs(),
            ),
            'mailersend' => new MailersendDriver(
                config('nettmail.drivers.mailersend.api_key'),
                ...$this->httpClientArgs(),
            ),
            'mailgun' => new MailgunDriver(
                config('nettmail.drivers.mailgun.api_key'),
                config('nettmail.drivers.mailgun.domain'),
                ...$this->httpClientArgs(),
            ),
            'postmark' => new PostmarkDriver(
                config('nettmail.drivers.postmark.server_token'),
                ...$this->httpClientArgs(),
            ),
            'ses' => new SesDriver(
                config('nettmail.drivers.ses.access_key_id'),
                config('nettmail.drivers.ses.secret_access_key'),
                config('nettmail.drivers.ses.region'),
                ...$this->httpClientArgs(),
            ),
            default => throw new \InvalidArgumentException("Unsupported NettMail driver [{$driver}]."),
        };
    }

    /**
     * @return array{0: GuzzleClient, 1: HttpFactory, 2: HttpFactory}
     */
    private function httpClientArgs(): array
    {
        $factory = new HttpFactory;

        return [new GuzzleClient, $factory, $factory];
    }
}

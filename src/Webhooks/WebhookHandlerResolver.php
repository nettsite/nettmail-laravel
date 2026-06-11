<?php

namespace NettSite\NettMail\Webhooks;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Nettsite\NettMail\Core\Contracts\WebhookHandlerContract;
use Nettsite\NettMail\Core\Drivers\Webhooks\MailersendWebhookHandler;
use Nettsite\NettMail\Core\Drivers\Webhooks\MailgunWebhookHandler;
use Nettsite\NettMail\Core\Drivers\Webhooks\PostmarkWebhookHandler;
use Nettsite\NettMail\Core\Drivers\Webhooks\ResendWebhookHandler;
use Nettsite\NettMail\Core\Drivers\Webhooks\SesWebhookHandler;

final class WebhookHandlerResolver
{
    public function resolve(string $provider): ?WebhookHandlerContract
    {
        return match ($provider) {
            'resend' => new ResendWebhookHandler,
            'mailersend' => new MailersendWebhookHandler,
            'mailgun' => new MailgunWebhookHandler,
            'postmark' => new PostmarkWebhookHandler,
            'ses' => new SesWebhookHandler(new GuzzleClient, new HttpFactory),
            default => null,
        };
    }
}

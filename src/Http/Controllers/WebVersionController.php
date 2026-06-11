<?php

namespace NettSite\NettMail\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Nettsite\NettMail\Core\Domain\Contacts\UnsubscribeTokenGenerator;
use Nettsite\NettMail\Core\Domain\Templates\MergeTagRenderer;
use NettSite\NettMail\Models\Send;

final class WebVersionController extends Controller
{
    public function __construct(
        private readonly UnsubscribeTokenGenerator $tokens,
    ) {}

    public function show(string $sendToken): Response
    {
        $send = Send::query()->where('send_token', $sendToken)->first();

        if ($send === null || $send->campaign_id === null) {
            return response('', 404);
        }

        $campaign = $send->campaign;
        $contact = $send->contact;

        $unsubscribeUrl = rtrim((string) config('app.url'), '/')
            .'/'.config('nettmail.routes.prefix')
            .'/unsubscribe/'.$this->tokens->generate($contact->id, $campaign->list_id);

        $html = (new MergeTagRenderer)->render((string) $campaign->template->html, [
            'first_name' => (string) $contact->first_name,
            'last_name' => (string) $contact->last_name,
            'email' => $contact->email,
            'unsubscribe_url' => $unsubscribeUrl,
        ]);

        return response($html)->header('Content-Type', 'text/html');
    }
}

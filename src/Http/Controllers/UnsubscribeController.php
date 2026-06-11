<?php

namespace NettSite\NettMail\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Domain\Contacts\UnsubscribeToken;
use Nettsite\NettMail\Core\Domain\Contacts\UnsubscribeTokenGenerator;
use NettSite\NettMail\Models\Contact;
use NettSite\NettMail\Models\ListContact;

final class UnsubscribeController extends Controller
{
    public function __construct(
        private readonly UnsubscribeTokenGenerator $tokens,
    ) {}

    public function show(string $token): Response|View
    {
        $unsubscribeToken = $this->tokens->verify($token);

        if ($unsubscribeToken === null) {
            return response('This unsubscribe link is invalid.', 404);
        }

        $this->unsubscribe($unsubscribeToken);

        return view('nettmail::unsubscribe', [
            'token' => $token,
            'listId' => $unsubscribeToken->listId,
        ]);
    }

    public function unsubscribeAll(string $token): Response|View
    {
        $unsubscribeToken = $this->tokens->verify($token);

        if ($unsubscribeToken === null) {
            return response('This unsubscribe link is invalid.', 404);
        }

        $this->unsubscribeFromAll($unsubscribeToken->contactId);

        return view('nettmail::unsubscribe-all');
    }

    public function oneClick(string $token): Response
    {
        $unsubscribeToken = $this->tokens->verify($token);

        if ($unsubscribeToken === null) {
            return response('', 404);
        }

        $this->unsubscribe($unsubscribeToken);

        return response('', 200);
    }

    private function unsubscribe(UnsubscribeToken $unsubscribeToken): void
    {
        if ($unsubscribeToken->listId === null) {
            $this->unsubscribeFromAll($unsubscribeToken->contactId);

            return;
        }

        ListContact::query()
            ->where('contact_id', $unsubscribeToken->contactId)
            ->where('list_id', $unsubscribeToken->listId)
            ->update([
                'status' => MembershipStatus::Unsubscribed,
                'unsubscribed_at' => now(),
            ]);
    }

    private function unsubscribeFromAll(string $contactId): void
    {
        Contact::query()->whereKey($contactId)->update([
            'global_unsubscribed_at' => now(),
        ]);
    }
}

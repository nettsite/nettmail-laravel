<?php

namespace NettSite\NettMail\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Domain\Contacts\OptInTokenGenerator;
use NettSite\NettMail\Models\ListContact;

final class OptInController extends Controller
{
    public function __construct(
        private readonly OptInTokenGenerator $tokens,
    ) {}

    public function confirm(string $token): Response|View
    {
        $optInToken = $this->tokens->verify($token);

        if ($optInToken === null) {
            return response('This confirmation link has expired.', 410);
        }

        $membership = ListContact::query()
            ->where('contact_id', $optInToken->contactId)
            ->where('list_id', $optInToken->listId)
            ->first();

        if ($membership === null) {
            return response('', 404);
        }

        if ($membership->status === MembershipStatus::Pending) {
            $membership->status = MembershipStatus::Subscribed;
            $membership->subscribed_at = now();
            $membership->save();
        }

        return view('nettmail::opt-in-confirmed');
    }
}

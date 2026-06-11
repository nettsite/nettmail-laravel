<?php

namespace NettSite\NettMail\Mailbox;

use Nettsite\NettMail\Core\Contracts\MailboxContract;
use Nettsite\NettMail\Core\Domain\Bounces\MailboxMessage;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

final class ImapMailbox implements MailboxContract
{
    private ?Client $client = null;

    /** @var array<string, Message> */
    private array $messages = [];

    /**
     * @param  array{host: ?string, port: int, username: ?string, password: ?string, encryption: ?string, folder: string}  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function fetchUnseenMessages(): array
    {
        $folder = $this->client()->getFolder($this->config['folder']);

        $messages = [];

        foreach ($folder->query()->whereUnseen()->get() as $message) {
            $id = (string) $message->uid;
            $this->messages[$id] = $message;
            $messages[] = new MailboxMessage($id, $message->getRawBody());
        }

        return $messages;
    }

    public function moveMessage(string $id, string $folder): void
    {
        $this->messages[$id]->move($folder);
    }

    private function client(): Client
    {
        if ($this->client === null) {
            $this->client = (new ClientManager)->make([
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'encryption' => $this->config['encryption'],
                'validate_cert' => true,
                'username' => $this->config['username'],
                'password' => $this->config['password'],
                'protocol' => 'imap',
            ]);

            $this->client->connect();
        }

        return $this->client;
    }
}

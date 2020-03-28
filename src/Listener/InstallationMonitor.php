<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP\Listener;

use Innmind\Infrastructure\AMQP\Event\UserWasAdded;
use Innmind\InstallationMonitor\{
    Client,
    Event,
};
use Innmind\Immutable\Map;

final class InstallationMonitor
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __invoke(UserWasAdded $event): void
    {
        $this->client->send(new Event(
            new Event\Name('amqp.user_added'),
            (new Map('string', 'variable'))
                ->put('name', $event->user())
                ->put('password', $event->password())
        ));
    }
}

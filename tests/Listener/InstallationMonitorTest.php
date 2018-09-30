<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\AMQP\Listener;

use Innmind\Infrastructure\AMQP\{
    Listener\InstallationMonitor,
    Event\UserWasAdded,
};
use Innmind\InstallationMonitor\{
    Client,
    Event,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class InstallationMonitorTest extends TestCase
{
    public function testInvokation()
    {
        $dispatch = new InstallationMonitor(
            $client = $this->createMock(Client::class)
        );
        $client
            ->expects($this->once())
            ->method('send')
            ->with(new Event(
                new Event\Name('amqp.user_added'),
                (new Map('string', 'variable'))
                    ->put('name', 'user')
                    ->put('password', 'watev')
            ));

        $this->assertNull($dispatch(new UserWasAdded('user', 'watev')));
    }
}

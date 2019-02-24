<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP;

use Innmind\Infrastructure\AMQP\{
    Command\Install,
    Command\SetupUsers,
    Event\UserWasAdded,
    Listener\InstallationMonitor,
};
use Innmind\CLI\Commands;
use Innmind\RabbitMQ\Management\Control\Control;
use Innmind\OperatingSystem\OperatingSystem;
use function Innmind\InstallationMonitor\bootstrap as monitor;
use function Innmind\EventBus\bootstrap as eventBus;
use Innmind\Immutable\Map;

function bootstrap(OperatingSystem $os): Commands
{
    $monitor = monitor($os);

    return new Commands(
        new Install($os->control()),
        new SetupUsers(
            new Control($os->control()),
            eventBus()['bus'](
                Map::of('string', 'callable')
                    (
                        UserWasAdded::class,
                        new InstallationMonitor(
                            $monitor['client']['silence'](
                                $monitor['client']['ipc']()
                            )
                        )
                    )
            )
        )
    );
}

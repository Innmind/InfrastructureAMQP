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
use Innmind\Server\Control\ServerFactory;
use Innmind\RabbitMQ\Management\Control\Control;
use Innmind\EventBus\EventBus;
use function Innmind\InstallationMonitor\bootstrap as monitor;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
};

function bootstrap(): Commands
{
    $monitor = monitor();

    $server = ServerFactory::build();

    return new Commands(
        new Install($server),
        new SetupUsers(
            new Control($server),
            new EventBus(
                (new Map('string', SetInterface::class))
                    ->put(
                        UserWasAdded::class,
                        Set::of(
                            'callable',
                            new InstallationMonitor(
                                $monitor['client']['silence'](
                                    $monitor['client']['socket']()
                                )
                            )
                        )
                    )
            )
        )
    );
}

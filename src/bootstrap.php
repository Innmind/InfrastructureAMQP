<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP;

use Innmind\Infrastructure\AMQP\{
    Command\Install,
    Command\SetupUsers,
};
use Innmind\CLI\Commands;
use Innmind\Server\Control\ServerFactory;
use Innmind\RabbitMQ\Management\Control\Control;
use Innmind\EventBus\EventBus;
use Innmind\Immutable\{
    Map,
    SetInterface,
};

function bootstrap(): Commands
{
    $server = ServerFactory::build();

    return new Commands(
        new Install($server),
        new SetupUsers(
            new Control($server),
            new EventBus(new Map('string', SetInterface::class))
        )
    );
}

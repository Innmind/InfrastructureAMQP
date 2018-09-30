<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP\Command;

use Innmind\Infrastructure\AMQP\Event\UserWasAdded;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\RabbitMQ\Management\Control;
use Innmind\EventBus\EventBusInterface;

final class SetupUsers implements Command
{
    private $rabbitmq;
    private $bus;

    public function __construct(Control $rabbitmq, EventBusInterface $bus)
    {
        $this->rabbitmq = $rabbitmq;
        $this->bus = $bus;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $this->rabbitmq->users()->declare('monitor', $monitor = \sha1(\random_bytes(32)), 'administrator');
        $this->rabbitmq->users()->declare('consumer', $consumer = \sha1(\random_bytes(32)));
        $this->rabbitmq->permissions()->declare('/', 'monitor', '.*', '.*', '.*');
        $this->rabbitmq->permissions()->declare('/', 'consumer', '.*', '.*', '.*');
        $this->rabbitmq->users()->delete('guest');

        $this->bus->dispatch(new UserWasAdded('monitor', $monitor));
        $this->bus->dispatch(new UserWasAdded('consumer', $consumer));
    }

    public function __toString(): string
    {
        return <<<USAGE
setup-users

This will create a user from administration and another one for clients
USAGE;
    }
}

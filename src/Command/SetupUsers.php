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
use Innmind\EventBus\EventBus;

final class SetupUsers implements Command
{
    private Control $rabbitmq;
    private EventBus $dispatch;

    public function __construct(Control $rabbitmq, EventBus $dispatch)
    {
        $this->rabbitmq = $rabbitmq;
        $this->dispatch = $dispatch;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $this->rabbitmq->users()->declare('monitor', $monitor = \sha1(\random_bytes(32)), 'administrator');
        $this->rabbitmq->users()->declare('consumer', $consumer = \sha1(\random_bytes(32)));
        $this->rabbitmq->permissions()->declare('/', 'monitor', '.*', '.*', '.*');
        $this->rabbitmq->permissions()->declare('/', 'consumer', '.*', '.*', '.*');
        $this->rabbitmq->users()->delete('guest');

        ($this->dispatch)(new UserWasAdded('monitor', $monitor));
        ($this->dispatch)(new UserWasAdded('consumer', $consumer));
    }

    public function toString(): string
    {
        return <<<USAGE
setup-users

This will create a user from administration and another one for clients
USAGE;
    }
}

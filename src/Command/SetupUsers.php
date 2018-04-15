<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP\Command;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\RabbitMQ\Management\Control;

final class SetupUsers implements Command
{
    private $rabbitmq;

    public function __construct(Control $rabbitmq)
    {
        $this->rabbitmq = $rabbitmq;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $this->rabbitmq->users()->declare('monitor', \sha1(\random_bytes(32)), 'administrator');
        $this->rabbitmq->users()->declare('consumer', \sha1(\random_bytes(32)));
        $this->rabbitmq->permissions()->declare('/', 'monitor', '.*', '.*', '.*');
        $this->rabbitmq->permissions()->declare('/', 'consumer', '.*', '.*', '.*');
        $this->rabbitmq->users()->delete('guest');
    }

    public function __toString(): string
    {
        return <<<USAGE
setup-users

This will create a user from administration and another one for clients
USAGE;
    }
}

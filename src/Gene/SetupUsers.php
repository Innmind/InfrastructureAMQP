<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP\Gene;

use Innmind\Genome\{
    Gene,
    History,
    Exception\ExpressionFailed,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\Server;
use Innmind\RabbitMQ\Management\{
    Control\Control,
    Exception\ManagementPluginFailedToRun,
};
use Innmind\Immutable\Map;

final class SetupUsers implements Gene
{
    public function name(): string
    {
        return 'AMQP setup users';
    }

    public function express(
        OperatingSystem $local,
        Server $target,
        History $history
    ): History {
        $monitor = \sha1(\random_bytes(32));
        $consumer = \sha1(\random_bytes(32));

        try {
            $rabbitmq = new Control($target);
            $rabbitmq->users()->declare('monitor', $monitor, 'administrator');
            $rabbitmq->users()->declare('consumer', $consumer);
            $rabbitmq->permissions()->declare('/', 'monitor', '.*', '.*', '.*');
            $rabbitmq->permissions()->declare('/', 'consumer', '.*', '.*', '.*');
            $rabbitmq->users()->delete('guest');
        } catch (ManagementPluginFailedToRun $e) {
            throw new ExpressionFailed($this->name());
        }

        /** @var Map<string, mixed> */
        $payload = Map::of('string', 'mixed');

        return $history
            ->add(
                'amqp.user_added',
                $payload
                    ('name', 'monitor')
                    ('password', $monitor),
            )
            ->add(
                'amqp.user_added',
                $payload
                    ('name', 'consumer')
                    ('password', $consumer),
            );
    }
}

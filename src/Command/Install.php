<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP\Command;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\{
    Server,
    Server\Command as ServerCommand,
    Server\Process\ExitCode,
};
use Innmind\Immutable\Stream;

final class Install implements Command
{
    private $server;
    private $actions;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->actions = Stream::of(
            'string',
            'echo "deb https://dl.bintray.com/rabbitmq/debian stretch main" | tee /etc/apt/sources.list.d/bintray.rabbitmq.list',
            'wget -O- https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc | apt-key add -',
            'apt-get update',
            'apt-get install libsctp1 -y',
            'wget http://packages.erlang-solutions.com/site/esl/esl-erlang/FLAVOUR_1_general/esl-erlang_20.3-1~debian~stretch_amd64.deb',
            'dpkg -i esl-erlang_20.3-1~debian~stretch_amd64.deb',
            'rm esl-erlang_20.3-1~debian~stretch_amd64.deb',
            'apt-get install rabbitmq-server -y',
            'rabbitmq-plugins enable rabbitmq_management',
            'wget http://localhost:15672/cli/rabbitmqadmin',
            'chmod +x rabbitmqadmin',
            'mv rabbitmqadmin /usr/local/bin/rabbitmqadmin',
            'rabbitmqctl set_vm_memory_high_watermark 0.5',
            'rabbitmqctl set_disk_free_limit mem_relative 2.0'
        );
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $processes = $this->server->processes();
        $exitCode = $this->actions->reduce(
            new ExitCode(0),
            static function(ExitCode $exitCode, string $action) use ($processes): ExitCode {
                if (!$exitCode->isSuccessful()) {
                    return $exitCode;
                }

                return $processes
                    ->execute(ServerCommand::foreground($action))
                    ->wait()
                    ->exitCode();
            }
        );
        $env->exit($exitCode->toInt());
    }

    public function __toString(): string
    {
        return <<<USAGE
install

This will install rabbitmq on the machine

It only works for Debian Stretch as we need to configure the repositories
from which we'll fetch rabbitmq package
USAGE;
    }
}

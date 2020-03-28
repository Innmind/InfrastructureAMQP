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
use Innmind\Immutable\{
    Sequence,
    Str,
};

final class Install implements Command
{
    private Server $server;
    private Sequence $actions;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->actions = Sequence::of(
            'string',
            'echo "deb https://dl.bintray.com/rabbitmq/debian stretch main" | tee /etc/apt/sources.list.d/bintray.rabbitmq.list',
            'wget -O- https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc | apt-key add -',
            'apt-get update',
            //whithout installing and uninstalling the esl-erlang package somehow
            //won't be able to install
            'apt-get install libsctp1 erlang erlang-base -y',
            'apt-get remove erlang erlang-base erlang-base-hipe -y',
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
        $output = $env->output();
        $exitCode = $this->actions->reduce(
            new ExitCode(0),
            static function(ExitCode $exitCode, string $action) use ($processes, $output): ExitCode {
                if (!$exitCode->isSuccessful()) {
                    return $exitCode;
                }

                $output->write(Str::of($action)->append("\n"));

                $process = $processes->execute(ServerCommand::foreground($action));
                $process->wait();

                return $process->exitCode();
            }
        );
        $env->exit($exitCode->toInt());
    }

    public function toString(): string
    {
        return <<<USAGE
install

This will install rabbitmq on the machine

It only works for Debian Stretch as we need to configure the repositories
from which we'll fetch rabbitmq package
USAGE;
    }
}

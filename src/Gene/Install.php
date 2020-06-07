<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\AMQP\Gene;

use Innmind\Genome\{
    Gene,
    History,
    Exception\PreConditionFailed,
    Exception\ExpressionFailed,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Script,
    Exception\ScriptFailed,
};

final class Install implements Gene
{
    public function name(): string
    {
        return 'AMQP install';
    }

    public function express(
        OperatingSystem $local,
        Server $target,
        History $history
    ): History {
        try {
            $preCondition = new Script(
                Command::foreground('which')->withArgument('apt'),
            );
            $preCondition($target);
        } catch (ScriptFailed $e) {
            throw new PreConditionFailed('apt is missing');
        }

        try {
            $install = new Script(
                Command::foreground('apt')
                    ->withArgument('update')
                    ->withShortOption('y'),
                Command::foreground('apt')
                    ->withArgument('install')
                    ->withShortOption('y')
                    ->withArgument('curl')
                    ->withArgument('gnupg'),
                Command::foreground('curl')
                    ->withShortOption('fsSL')
                    ->withArgument('https://github.com/rabbitmq/signing-keys/releases/download/2.0/rabbitmq-release-signing-key.asc')
                    ->pipe(
                        Command::foreground('apt-key')
                            ->withArgument('add')
                            ->withArgument('-'),
                    ),
                Command::foreground('apt')
                    ->withArgument('install')
                    ->withShortOption('y')
                    ->withArgument('apt-transport-https'),
                Command::foreground('echo')
                    ->withArgument('deb https://dl.bintray.com/rabbitmq-erlang/debian bionic erlang')
                    ->pipe(
                        Command::foreground('tee')
                            ->withShortOption('a')
                            ->withArgument('/etc/apt/sources.list.d/bintray.rabbitmq.list'),
                    ),
                Command::foreground('echo')
                    ->withArgument('deb https://dl.bintray.com/rabbitmq/debian bionic main')
                    ->pipe(
                        Command::foreground('tee')
                            ->withShortOption('a')
                            ->withArgument('/etc/apt/sources.list.d/bintray.rabbitmq.list'),
                    ),
                Command::foreground('apt')
                    ->withArgument('update')
                    ->withShortOption('y'),
                Command::foreground('apt')
                    ->withArgument('install')
                    ->withShortOption('y')
                    ->withArgument('rabbitmq-server')
                    ->withOption('fix-missing'),
                Command::foreground('rabbitmq-plugins')
                    ->withArgument('enable')
                    ->withArgument('rabbitmq_management'),
                Command::foreground('wget')
                    ->withArgument('http://localhost:15672/cli/rabbitmqadmin'),
                Command::foreground('chmod')
                    ->withArgument('+x')
                    ->withArgument('rabbitmqadmin'),
                Command::foreground('mv')
                    ->withArgument('rabbitmqadmin')
                    ->withArgument('/usr/local/bin/rabbitmqadmin'),
                Command::foreground('rabbitmqctl')
                    ->withArgument('set_vm_memory_high_watermark')
                    ->withArgument('0.5'),
                Command::foreground('rabbitmqctl')
                    ->withArgument('set_disk_free_limit')
                    ->withArgument('mem_relative')
                    ->withArgument('2.0'),
            );
            $install($target);
        } catch (ScriptFailed $e) {
            throw new ExpressionFailed($this->name());
        }

        return $history;
    }
}

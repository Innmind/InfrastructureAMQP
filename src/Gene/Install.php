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
                Command::foreground('echo')
                    ->withArgument('deb https://dl.bintray.com/rabbitmq/debian stretch main')
                    ->pipe(
                        Command::foreground('tee')
                            ->withArgument('/etc/apt/sources.list.d/bintray.rabbitmq.list'),
                    ),
                Command::foreground('wget')
                    ->withShortOption('O', '-')
                    ->withArgument('https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc')
                    ->pipe(
                        Command::foreground('apt-key')
                            ->withArgument('add')
                            ->withArgument('-'),
                    ),
                Command::foreground('apt')->withArgument('update'),
                // whithout installing and uninstalling the esl-erlang package
                // somehow won't be able to install
                Command::foreground('apt')
                    ->withArgument('install')
                    ->withShortOption('y')
                    ->withArgument('libsctp1')
                    ->withArgument('erlang')
                    ->withArgument('erlang-base'),
                Command::foreground('apt')
                    ->withArgument('remove')
                    ->withShortOption('y')
                    ->withArgument('erlang')
                    ->withArgument('erlang-base')
                    ->withArgument('erlang-base-hipe'),
                Command::foreground('wget')
                    ->withArgument('http://packages.erlang-solutions.com/site/esl/esl-erlang/FLAVOUR_1_general/esl-erlang_20.3-1~debian~stretch_amd64.deb'),
                Command::foreground('dpkg')
                    ->withShortOption('i', 'esl-erlang_20.3-1~debian~stretch_amd64.deb'),
                Command::foreground('rm')
                    ->withArgument('esl-erlang_20.3-1~debian~stretch_amd64.deb'),
                Command::foreground('apt')
                    ->withArgument('install')
                    ->withShortOption('y')
                    ->withArgument('rabbitmq-server'),
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

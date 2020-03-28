<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\AMQP\Command;

use Innmind\Infrastructure\AMQP\Command\Install;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
};
use PHPUnit\Framework\TestCase;

class InstallTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Install($this->createMock(Server::class))
        );
    }

    public function testInvokation()
    {
        $install = new Install(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->exactly(15))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(function($command) {
                    return $command->toString() === 'echo "deb https://dl.bintray.com/rabbitmq/debian stretch main" | tee /etc/apt/sources.list.d/bintray.rabbitmq.list';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'wget -O- https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc | apt-key add -';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'apt-get update';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'apt-get install libsctp1 erlang erlang-base -y';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'apt-get remove erlang erlang-base erlang-base-hipe -y';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'wget http://packages.erlang-solutions.com/site/esl/esl-erlang/FLAVOUR_1_general/esl-erlang_20.3-1~debian~stretch_amd64.deb';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'dpkg -i esl-erlang_20.3-1~debian~stretch_amd64.deb';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'rm esl-erlang_20.3-1~debian~stretch_amd64.deb';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'apt-get install rabbitmq-server -y';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'rabbitmq-plugins enable rabbitmq_management';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'wget http://localhost:15672/cli/rabbitmqadmin';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'chmod +x rabbitmqadmin';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'mv rabbitmqadmin /usr/local/bin/rabbitmqadmin';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'rabbitmqctl set_vm_memory_high_watermark 0.5';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'rabbitmqctl set_disk_free_limit mem_relative 2.0';
                })],
            )
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->exactly(15))
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->exactly(15))
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(0);

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
    }

    public function testExitWithErrorCodeWhenOneActionFailed()
    {
        $install = new Install(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(function($command) {
                    return $command->toString() === 'echo "deb https://dl.bintray.com/rabbitmq/debian stretch main" | tee /etc/apt/sources.list.d/bintray.rabbitmq.list';
                })],
                [$this->callback(function($command) {
                    return $command->toString() === 'wget -O- https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc | apt-key add -';
                })]
            )
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->exactly(2))
            ->method('wait');
        $process
            ->expects($this->exactly(2))
            ->method('exitCode')
            ->will($this->onConsecutiveCalls(
                new ExitCode(0),
                new ExitCode(1)
            ));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
    }

    public function testUsage()
    {
        $expected = <<<USAGE
install

This will install rabbitmq on the machine

It only works for Debian Stretch as we need to configure the repositories
from which we'll fetch rabbitmq package
USAGE;

        $this->assertSame($expected, (new Install($this->createMock(Server::class)))->toString());
    }
}

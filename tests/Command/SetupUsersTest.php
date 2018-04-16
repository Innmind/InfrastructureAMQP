<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\AMQP\Command;

use Innmind\Infrastructure\AMQP\Command\SetupUsers;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\RabbitMQ\Management\{
    Control,
    Control\Users,
    Control\Permissions,
};
use PHPUnit\Framework\TestCase;

class SetupUsersTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new SetupUsers($this->createMock(Control::class))
        );
    }

    public function testInvokation()
    {
        $setup = new SetupUsers(
            $control = $this->createMock(Control::class)
        );
        $control
            ->expects($this->exactly(3))
            ->method('users')
            ->willReturn($users = $this->createMock(Users::class));
        $control
            ->expects($this->exactly(2))
            ->method('permissions')
            ->willReturn($permissions = $this->createMock(Permissions::class));
        $users
            ->expects($this->at(0))
            ->method('declare')
            ->with(
                'monitor',
                $this->callback(static function($password): bool {
                    return strlen($password) === 40;
                }),
                'administrator'
            );
        $users
            ->expects($this->at(1))
            ->method('declare')
            ->with(
                'consumer',
                $this->callback(static function($password): bool {
                    return strlen($password) === 40;
                })
            );
        $users
            ->expects($this->at(2))
            ->method('delete')
            ->with('guest');
        $permissions
            ->expects($this->at(0))
            ->method('declare')
            ->with('/', 'monitor', '.*', '.*', '.*');
        $permissions
            ->expects($this->at(1))
            ->method('declare')
            ->with('/', 'consumer', '.*', '.*', '.*');

        $this->assertNull($setup(
            $this->createMock(Environment::class),
            new Arguments,
            new Options
        ));
    }

    public function testUsage()
    {
        $expected = <<<USAGE
setup-users

This will create a user from administration and another one for clients
USAGE;

        $this->assertSame(
            $expected,
            (string) new SetupUsers($this->createMock(Control::class))
        );
    }
}

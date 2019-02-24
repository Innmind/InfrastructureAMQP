<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\AMQP;

use function Innmind\Infrastructure\AMQP\bootstrap;
use Innmind\CLI\Commands;
use Innmind\OperatingSystem\OperatingSystem;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $this->assertInstanceOf(
            Commands::class,
            bootstrap($this->createMock(OperatingSystem::class))
        );
    }
}

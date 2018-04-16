<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\AMQP;

use Innmind\CLI\Commands;
use Innmind\Compose\ContainerBuilder\ContainerBuilder;
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testInterface()
    {
        $container = (new ContainerBuilder)(
            new Path('container.yml'),
            new Map('string', 'mixed')
        );

        $this->assertInstanceOf(Commands::class, $container->get('commands'));
    }
}

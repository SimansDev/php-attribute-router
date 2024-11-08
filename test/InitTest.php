<?php
declare(strict_types=1);

use AttributeRouter\DTO\RouteDto;
use PHPUnit\Framework\TestCase;

final class InitTest extends TestCase
{
    public function testRouteDtoCreation(): void
    {
        $router = new RouteDto(
            class: 'test', method: 'test', type: 'test'
        );

        self::assertInstanceOf(RouteDto::class, $router);
    }
}

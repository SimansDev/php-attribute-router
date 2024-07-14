<?php

namespace AttributeRouter\DTO;

final class RouteDto
{
    public function __construct(
        public string $class,
        public string $method,
        public string $type,
    )
    {
    }

    public function returnAsArray(): array
    {
        return [$this->class, $this->method];
    }
}

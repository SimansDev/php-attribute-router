<?php

namespace AttributeRouter\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        private readonly string $path,
        private readonly string $method,
    )
    {
    }
}

<?php

namespace AttributeRouter\Exception;

use Exception;

final class RouteNotFoundException extends Exception
{
    public function __construct(string $key = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Route %s Not Found', $key), $code, $previous);
    }
}


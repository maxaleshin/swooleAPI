<?php

namespace SwooleAPI\Routing\Attributes;

use Attribute;

/**
 * Базовый атрибут маршрута
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public array $middlewares = [],
        public string $name = ''
    ) {
    }
}
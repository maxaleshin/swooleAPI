<?php

namespace SwooleAPI\Routing\Attributes;

use Attribute;

/**
 * Базовый атрибут маршрута
 */
#[Attribute(Attribute::TARGET_METHOD)] // этот мета атрибут гарантирует, что атрибуты маршрутизации можно применять только к методам
class Route
{
    public function __construct(
        public string $path,
        public array $middlewares = [],
        public string $name = ''
    ) {
    }
}
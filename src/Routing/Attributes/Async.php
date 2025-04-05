<?php

namespace SwooleAPI\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Async extends Route
{
    public bool $isAsync = true;

    public function __construct(
        public string $path,
        public array $middlewares = [],
        public string $name = ''
    ) {
        parent::__construct($path, $middlewares, $name);
    }
}
<?php

namespace Mb\DoctrineLogBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Log
{

    public function __construct(
        public ?string $expression = null
    ) {}
}

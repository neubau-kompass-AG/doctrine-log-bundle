<?php

namespace Mb\DoctrineLogBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Loggable
{
    const STRATEGY_EXCLUDE_ALL = 'exclude_all';
    const STRATEGY_INCLUDE_ALL = 'include_all';

    public function __construct(

        /** @Enum({"exclude_all", "include_all"}) */
        public string $strategy = self::STRATEGY_INCLUDE_ALL,
        /** Expression, what to log on delete requests (to make log-analysis easier) */
        public ?string $onDeleteLog = null
    )
    {
    }
}

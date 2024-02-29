<?php

namespace Mb\DoctrineLogBundle\Tests\Service;

use Mb\DoctrineLogBundle\Service\AttributeReader;
use Mb\DoctrineLogBundle\Tests\Service\AttributeTestClasses\LoggableClass;
use Mb\DoctrineLogBundle\Tests\Service\AttributeTestClasses\LoggableExcludeDefaultClass;
use Mb\DoctrineLogBundle\Tests\Service\AttributeTestClasses\NonLoggableClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class AttributeReaderTest extends TestCase
{

    #[TestWith([NonLoggableClass::class, null, false])]
    #[TestWith([LoggableClass::class, null, true])]
    #[TestWith([LoggableClass::class, 'loggableProp', true])]
    #[TestWith([LoggableClass::class, 'nonLoggableProp', false])]
    #[TestWith([LoggableExcludeDefaultClass::class, 'loggableProp', true])]
    #[TestWith([LoggableExcludeDefaultClass::class, 'nonLoggableProp', false])]
    public function testIsLoggable(string $class, ?string $prop, bool $result)
    {
        $this->assertEquals($result, AttributeReader::isLoggable(new $class(), $prop));
    }
}
<?php

namespace Mb\DoctrineLogBundle\Service;

use Mb\DoctrineLogBundle\Attribute\Exclude;
use Mb\DoctrineLogBundle\Attribute\Log;
use Mb\DoctrineLogBundle\Attribute\Loggable;
use ReflectionClass;

class AttributeReader
{

    /**
     * Init the entity
     *
     * @param object $entity
     * @throws \ReflectionException
     */
    private static function getClassAttributeInstance(object $entity): ?Loggable
    {
        $class = new ReflectionClass(str_replace('Proxies\__CG__\\', '', get_class($entity)));
        $attribute = $class->getAttributes(Loggable::class)[0] ?? null;
        if(null !== $attribute && $instance = $attribute->newInstance()) {
            return $instance;
        }

        return null;
    }

    /**
     * Check if class or property is loggable
     *
     * @param null|string $property
     * @return bool
     */
    public static function isLoggable(object $object, string $property = null): bool
    {
        $classAttribute = self::getClassAttributeInstance($object);

        if(null === $classAttribute) {
            return false;
        }
        return !$property ? $classAttribute instanceof Loggable : self::isPropertyLoggable($classAttribute, $object, $property);
    }
    
    public static function getOnDeleteLogExpression(object $object): ?string {
        $classAttribute = self::getClassAttributeInstance($object);

        if($classAttribute instanceof Loggable) {
            return $classAttribute->onDeleteLog;
        }

        return null;
    }

    /**
     * Check if propert is loggable
     *
     * @param object $entity
     * @param string $property
     * @return bool
     * @throws \ReflectionException
     */
    private static function isPropertyLoggable(Loggable $classAttribute, object $entity, string $property): bool
    {
        $property = new \ReflectionProperty(
            str_replace('Proxies\__CG__\\', '', get_class($entity)),
            $property
        );

        if ($classAttribute->strategy === Loggable::STRATEGY_EXCLUDE_ALL) {
            // check for log annotation
            $attribute = $property->getAttributes( Log::class)[0] ?? null;

            return $attribute !== null;
        }

        // include all strategy, check for exclude
        $attribute = $property->getAttributes(Exclude::class)[0] ?? null;

        return $attribute === null;
    }

    /**
     * @throws \ReflectionException
     */
    public static function getPropertyExpression(object $entity, string $property): ?string
    {
        $property = new \ReflectionProperty(
            str_replace('Proxies\__CG__\\', '', get_class($entity)),
            $property
        );

        $attribute = $property->getAttributes(Log::class)[0] ?? null;

        if (null !== $attribute && $instance = $attribute->newInstance()) {
            return $instance->expression;
        }

        return null;
    }
}

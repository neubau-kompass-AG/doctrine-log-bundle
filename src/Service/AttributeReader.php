<?php

namespace Mb\DoctrineLogBundle\Service;

use Mb\DoctrineLogBundle\Attribute\Exclude;
use Mb\DoctrineLogBundle\Attribute\Log;
use Mb\DoctrineLogBundle\Attribute\Loggable;
use Mb\DoctrineLogBundle\Exception\NotInitializedException;
use ReflectionClass;

class AttributeReader
{

    private ?Loggable $classAttribute = null;

    /**
     * @var object
     */
    private $entity;

    /**
     * Init the entity
     *
     * @param object $entity
     * @throws \ReflectionException
     */
    public function init($entity): void
    {
        $this->entity = $entity;
        $class = new ReflectionClass(str_replace('Proxies\__CG__\\', '', get_class($entity)));
        $attribute = $class->getAttributes(Loggable::class)[0] ?? null;
        if(null !== $attribute && $instance = $attribute->newInstance()) {
            $this->classAttribute = $instance;
        }
    }

    /**
     * Check if class or property is loggable
     *
     * @param null|string $property
     * @return bool
     */
    public function isLoggable($property = null)
    {
        if(null === $this->classAttribute) {
            throw new NotInitializedException('AttributeReader not initialized');
        }
        return !$property ? $this->classAttribute instanceof Loggable : $this->isPropertyLoggable($property);
    }
    
    public function getOnDeleteLogExpression(): ?string {
        if(null === $this->classAttribute) {
            throw new NotInitializedException('AttributeReader not initialized');
        }

        return $this->classAttribute->onDeleteLog;
    }

    /**
     * Check if propert is loggable
     *
     * @param $property
     * @return bool
     * @throws \ReflectionException
     */
    private function isPropertyLoggable($property)
    {
        if(null === $this->classAttribute) {
            throw new NotInitializedException('AttributeReader not initialized');
        }

        $property = new \ReflectionProperty(
            str_replace('Proxies\__CG__\\', '', get_class($this->entity)),
            $property
        );

        if ($this->classAttribute->strategy === Loggable::STRATEGY_EXCLUDE_ALL) {
            // check for log annotation
            $attribute = $property->getAttributes( Log::class)[0] ?? null;

            return $attribute instanceof Log;
        }

        // include all strategy, check for exclude
        $attribute = $property->getAttributes(Exclude::class)[0] ?? null;

        return !$attribute instanceof Exclude;
    }

    /**
     * @param $property
     * @return ?string
     * @throws \ReflectionException
     */
    public function getPropertyExpression($property)
    {
        $property = new \ReflectionProperty(
            str_replace('Proxies\__CG__\\', '', get_class($this->entity)),
            $property
        );

        $attribute = $property->getAttributes(Log::class)[0] ?? null;

        if ($instance = $attribute->newInstance()) {
            return $instance->expression;
        }

        return null;
    }
}

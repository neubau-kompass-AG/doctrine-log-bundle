<?php

namespace Mb\DoctrineLogBundle\Service;

use Doctrine\ORM\EntityManagerInterface;

use Mb\DoctrineLogBundle\Entity\Log as LogEntity;

/**
 * Class Logger
 * @package Mb\DoctrineLogBundle\Service
 */
class Logger
{
    /**
     * @var EntityManagerInterface $em
     */
    protected $em;

    /**
     * Logger constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Logs object change
     *
     * @param object $object
     * @param string $action
     * @param string $changes
     * @return LogEntity
     */
    public function log($object, $action, $changes = null) : LogEntity
    {
        $class = $this->em->getClassMetadata(get_class($object));
        $identifier = $class->getIdentifierValues($object);

        return new LogEntity(
            $class->getName(),
            implode(", ", $identifier),
            $action,
            $changes
        );
    }

    /**
     * Saves a log
     *
     * @param LogEntity $log
     * @return bool
     */
    public function save(LogEntity $log) : bool
    {
        $this->em->persist($log);
        $this->em->flush();

        return true;
    }
}

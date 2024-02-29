<?php

namespace Mb\DoctrineLogBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mb\DoctrineLogBundle\Entity\Log as LogEntity;
use Symfony\Bundle\SecurityBundle\Security;

class Logger
{

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Security $security)
    {
    }

    public function log(object $object, string $action, array $changes = null) : LogEntity
    {
        $class = $this->entityManager->getClassMetadata(get_class($object));
        $identifier = $class->getIdentifierValues($object);

        $username = null;
        if($this->security->getUser()) {
            $username = $this->security->getUser()->getUsername();
        }
        return new LogEntity(
            $class->getName(),
            implode(", ", $identifier),
            $action,
            $username,
            $changes
        );
    }

    public function save(LogEntity $log) : bool
    {
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return true;
    }
}

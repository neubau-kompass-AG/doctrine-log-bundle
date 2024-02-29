<?php

namespace Mb\DoctrineLogBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\BlameableInterface;
use Knp\DoctrineBehaviors\Model\Blameable\BlameableTrait;

#[ORM\Entity]
#[ORM\Table(name: "mb_entity_log")]
class Log
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column]
    protected ?int $id;

    #[ORM\Column(name: "object_class")]
    protected ?string $objectClass;

    #[ORM\Column(name: "foreign_key", type: "string", length: 1024)]
    protected ?string $foreignKey;

    #[ORM\Column]
    protected ?string $action;

    #[ORM\Column(type: "json")]
    protected ?array $changes;

    #[ORM\Column(name: "created_at", type: "datetime_immutable", nullable: false)]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(name: "changed_by", type: "string", length: 255, nullable: true)]
    protected ?string $changedBy;

    /**
     * Log constructor.
     */
    public function __construct(
        string $objectClass,
        string $foreignKey,
        string $action,
        ?string $changedBy,
        ?array $changes
    ) {
        $this->objectClass = $objectClass;
        $this->foreignKey = $foreignKey;
        $this->action = $action;
        $this->changes = $changes;

        $this->changedBy = $changedBy;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getObjectClass() : ?string
    {
        return $this->objectClass;
    }

    public function getForeignKey() : ?string
    {
        return $this->foreignKey;
    }

    public function getAction() : ?string
    {
        return $this->action;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

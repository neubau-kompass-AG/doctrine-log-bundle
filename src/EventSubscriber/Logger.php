<?php

namespace Mb\DoctrineLogBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Mb\DoctrineLogBundle\Service\AttributeReader;
use Mb\DoctrineLogBundle\Service\Logger as LoggerService;
use Mb\DoctrineLogBundle\Entity\Log as LogEntity;
use Mb\DoctrineLogBundle\Attribute\Loggable;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Throwable;

/**
 * Class Logger
 *
 * @package CoreBundle\EventListener
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter.Unused)
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class Logger implements EventSubscriber
{
    /** @var array<string, string> */
    protected $logs;

    protected ExpressionLanguage $expressionLanguage;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LoggerService $loggerService,
        protected LoggerInterface $monolog,
        protected AttributeReader $reader,
        protected array $ignoreProperties
    )
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->log($entity, LogEntity::ACTION_CREATE);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->log($entity, LogEntity::ACTION_UPDATE);
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->log($entity, LogEntity::ACTION_REMOVE);
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $collectionUpdate) {
            /** @var PersistentCollection $collectionUpdate */
            $owner = $collectionUpdate->getOwner();
            $this->reader->init($owner);

            $mapping = $collectionUpdate->getMapping();
            if (!$this->reader->isLoggable() || !$this->reader->isLoggable($mapping['fieldName'])) {
                return;
            }


            $expression = $this->reader->getPropertyExpression($mapping['fieldName']);

            $insertions = [];
            foreach ($collectionUpdate->getInsertDiff() as $relatedObject) {
                $insertions[] = $this->expressionLanguage->evaluate($expression, ['obj' => $relatedObject]);
            }

            $deletions = [];
            foreach ($collectionUpdate->getDeleteDiff() as $relatedObject) {
                $deletions[] = $this->expressionLanguage->evaluate($expression, ['obj' => $relatedObject]);
            }

            $changes = [];

            if (count($insertions)) {
                $changes['insertions'] = $insertions;
            }
            if (count($deletions)) {
                $changes['deletions'] = $deletions;
            }

            if ($changes != []) {
                foreach ($collectionUpdate as $item) {
                    $changes['newSet'][] = $this->expressionLanguage->evaluate($expression, ['obj' => $item]);
                }

                if (isset($this->logs[spl_object_hash($item)])) {
                    $changes = array_merge($this->logs[spl_object_hash($item)]->getChanges(), [$mapping['fieldName'] => $changes]);
                } else {
                    $changes = [$mapping['fieldName'] => $changes];
                }
                $this->logs[spl_object_hash($owner)] = $this->loggerService->log($owner, LogEntity::ACTION_UPDATE, $changes);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!empty($this->logs)) {
            foreach ($this->logs as $log) {
                $this->entityManager->persist($log);
            }

            $this->logs = [];
            $this->entityManager->flush();
        }
    }

    private function log(object $entity, string $action)
    {
       try {
            if ($this->reader->isLoggable()) {
                $changeSet = null;

                if ($action === LogEntity::ACTION_UPDATE) {
                    $uow = $this->entityManager->getUnitOfWork();

                    // get changes => should be already computed here (is a listener)
                    $changeSet = $uow->getEntityChangeSet($entity);
                    // if we have no changes left => don't create revision log
                    if (count($changeSet) == 0) {
                        return;
                    }


                    // just getting the changed objects ids
                    foreach ($changeSet as $key => &$values) {
                        if (in_array($key, $this->ignoreProperties) || !$this->reader->isLoggable($key)) {
                            // ignore configured properties
                            unset($changeSet[$key]);
                        }

                        $expression = $this->reader->getPropertyExpression($key);

                        if ($expression != null) {
                            if (is_object($values[0])) {
                                $values[0] = $this->expressionLanguage->evaluate($expression, ['obj' => $values[0]]);
                            }
                            if (is_object($values[1])) {
                                $values[1] = $this->expressionLanguage->evaluate($expression, ['obj' => $values[1]]);
                            }
                        } else {
                            if ($values[0] instanceof \DateTime) {
                                $values[0] = $values[0]->format('Y-m-d H:i:s');
                            }
                            if ($values[1] instanceof \DateTime) {
                                $values[1] = $values[1]->format('Y-m-d H:i:s');
                            }

                            if (is_object($values[0]) && method_exists($values[0], 'getId')) {
                                $values[0] = $values[0]->getId();
                            } elseif ($values[0] instanceof StreamInterface) {
                                $values[0] = (string)$values[1];
                            }

                            if (is_object($values[1]) && method_exists($values[1], 'getId')) {
                                $values[1] = $values[1]->getId();
                            } elseif ($values[1] instanceof StreamInterface) {
                                $values[1] = (string)$values[1];
                            }
                        }
                    }
                }

                if($action === LogEntity::ACTION_REMOVE) {
                    $expression = $this->reader->getOnDeleteLogExpression();

                    if(!empty($expression)) {
                        $changeSet['_remove'] = $this->expressionLanguage->evaluate($expression, ['obj' => $entity]);
                    }
                }

                if(empty($changeSet)) {
                    return;
                }

                if ($action !== LogEntity::ACTION_UPDATE) {
                    if (isset($this->logs[spl_object_hash(($entity))])) {
                        $changeSet = array_merge($changeSet, $this->logs[spl_object_hash($entity)]->getChanges());
                    }
                    $this->logs[spl_object_hash($entity)] = $this->loggerService->log(
                        $entity,
                        $action,
                        $changeSet
                    );

                }
            }
        } catch (\Exception $e) {
            $this->monolog->error($e->getMessage());
        }
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove,
            Events::onFlush,
            Events::postFlush
        ];
    }
}

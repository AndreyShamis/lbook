<?php

namespace App\Repository;

use App\Entity\LogBookMessage;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class LogBookMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookMessage::class);
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookMessage
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(array $criteria, $flush = true): LogBookMessage
    {
        $entity = new LogBookMessage();
        $entity->setMessage($criteria['message']);
        $entity->setChain($criteria['chain']);
        $entity->setMsgType($criteria['msgType']);
        $entity->setLogTime($criteria['logTime']);
        $entity->setTest($criteria['test']);
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush($entity);
        }
        return $entity;
    }

    /**
     * @param LogBookTest $test
     */
    public function deleteByTest(LogBookTest $test): void
    {
        $qd = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.test = :test')
            ->setParameter('test', $test->getId());
        $query = $qd->getQuery();
        $query->execute();
    }

    /**
     * @param int $testId
     */
    public function deleteByTestId(int $testId): void
    {
        $qd = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.test = :test')
            ->setParameter('test', $testId);
        $query = $qd->getQuery();
        $query->execute();
    }
}

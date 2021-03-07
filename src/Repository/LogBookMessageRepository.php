<?php

namespace App\Repository;

use App\Entity\LogBookMessage;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;

class LogBookMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookMessage::class);
    }

    public function createCustomTable(string $table_prefix) {
        try {
//            $productM = $this->_em->getRepository('App:LogBookMessage');
            $classMetaData = $this->_em->getClassMetadata('App:LogBookMessage');
            $classMetaData->setPrimaryTable(['name' => 'log_book_message_' . $table_prefix]);
            $schemaTool = new SchemaTool($this->_em);
            $schemaTool->createSchema(array($classMetaData));
        } catch (\Throwable $ex) {

        }

    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @param bool $persist
     * @return LogBookMessage
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(array $criteria, $flush = true, $persist = true): LogBookMessage
    {
        $entity = new LogBookMessage();
        $entity->setMessage($criteria['message']);
        $entity->setChain($criteria['chain']);
        $entity->setMsgType($criteria['msgType']);
        $entity->setLogTime($criteria['logTime']);
        $entity->setTest($criteria['test']);
        if ($persist) {
            $this->_em->persist($entity);
            if ($flush) {
                $this->_em->flush($entity);
            }
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

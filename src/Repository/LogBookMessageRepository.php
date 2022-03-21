<?php

namespace App\Repository;

use App\Entity\LogBookMessage;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;

class LogBookMessageRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookMessage::class);
    }

    /**
     * @param string $tableName
     */
    public function setCustomTable(string $tableName): void
    {
        try {
            $classMetaData = $this->_em->getClassMetadata('App:LogBookMessage');
            $classMetaData->setPrimaryTable(['name' => $tableName]);
        } catch (\Throwable $ex) {}
    }

    /**
     * @param string $table_prefix
     * @return string|null
     */
    public function createCustomTable(string $table_prefix): ?string
    {
        $dbName = null;
        try {
            $classMetaData = $this->_em->getClassMetadata('App:LogBookMessage');
            $dbName = 'log_book_message_' . $table_prefix;
            $classMetaData->setPrimaryTable(['name' => $dbName]);
            $schemaTool = new SchemaTool($this->_em);
            $schemaTool->createSchema(array($classMetaData));
        } catch (\Throwable $ex) {
        }
        return $dbName;
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

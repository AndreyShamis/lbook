<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookTestRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookTest::class);
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookTest
     */
    public function findOneOrCreate(array $criteria, $flush = false)
    {
        $add_hash = true;
        if (isset(self::$hashedData[$criteria['id']])) {
            $entity = self::$hashedData[$criteria['id']];
            $add_hash = false;
        } else {
            $entity = $this->findOneBy($criteria);
        }

        if (null === $entity) {
            $entity = new LogBookTest();
            $entity->setName($criteria['name']);
            //$entity->setVerdict($criteria['verdict']);
            $entity->setCycle($criteria['cycle']);
            $entity->setLogFile($criteria['logFile']);
            $entity->setLogFileSize($criteria['logFileSize']);
            $entity->setExecutionOrder($criteria['executionOrder']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            if($flush == true) {
                $this->_em->flush();
            }

        }
        if ($add_hash) {
            self::$hashedData[$criteria['id']] = $entity;
        }

        return $entity;
    }

    public function deleteByCycle(LogBookCycle $cycle)
    {
        $tests = $cycle->getTests();
        $msgRepo = $this->getEntityManager()->getRepository('App:LogBookMessage');
        foreach ($tests as $test){
            /** @var LogBookTest $test */
            $msgRepo->deleteByTestId($test->getId());
            $this->_em->detach($test);
        }
        $qd = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.cycle = :cycle')
            ->setParameter('cycle', $cycle->getId());
        $query = $qd->getQuery();
        $query->execute();

    }

    public function delete(LogBookTest $test)
    {
        $this->_em->remove($test);
        $this->_em->flush($test);
    }
    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('l')
            ->where('l.something = :value')->setParameter('value', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}

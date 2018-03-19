<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Stopwatch\Stopwatch;

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

    public function deleteByCycle(LogBookCycle &$cycle)
    {
//        $stopwatchGetRepo = new Stopwatch();
//        $stopwatchLogDelete = new Stopwatch();
//        $stopwatchGetRepo->start('getRepo');

        $msgRepo = $this->getEntityManager()->getRepository('App:LogBookMessage');
//        $eventGetRepo = $stopwatchGetRepo->stop('getRepo');
//        echo "eventGetRepo time (milliseconds):";
//        echo $eventGetRepo->getDuration() . "<br/> Log:";

//        $stopwatchLogDelete->start('logDelete');


//        $cycleRepo = $this->getEntityManager()->getRepository('App:LogBookTest');
//        $qd = $cycleRepo->createQueryBuilder('t')
//            ->where('t.cycle = :cycle')
//            ->setParameter('cycle', $cycle->getId());
//        $query = $qd->getQuery();
//        $iterableResult = $query->iterate();
//
//        while (($row = $iterableResult->next()) !== false) {;
//            /** @var LogBookTest $test */
//            $msgRepo->deleteByTestId($row[0]->getId());
////            $stopwatchLogDelete->lap("logDelete");
//            $this->delete($row[0]);
////            $stopwatchLogDelete->lap("logDelete");
//            $this->_em->detach($row[0]);
////            $event = $stopwatchLogDelete->stop('logDelete');
////            $ar = $event->getPeriods();
////            echo "Periods:<pre>";
////            print_r($ar);
////            echo "</pre>";
////            exit();
//        }

        foreach ($cycle->getTests() as $test){
            /** @var LogBookTest $test */
            $msgRepo->deleteByTestId($test->getId());
            //$stopwatchLogDelete->lap("logDelete");
            $this->delete($test);
            //$stopwatchLogDelete->lap("logDelete");
            $this->_em->detach($test);
            //$event = $stopwatchLogDelete->stop('logDelete');
//            $ar = $event->getPeriods();
//            echo "Periods:<pre>";
//            print_r($ar);
//            echo "</pre>";
//
//            exit();
        }
        /**
         * Additional clean | optional and may be removed
         */
        $qd = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.cycle = :cycle')
            ->setParameter('cycle', $cycle->getId());
        $query = $qd->getQuery();
        $query->execute();

    }

    public function delete(LogBookTest &$test)
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

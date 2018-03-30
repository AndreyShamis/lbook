<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookTestRepository extends ServiceEntityRepository
{

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookTest::class);
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookTest
     */
    public function findOneOrCreate(array $criteria, $flush = false): LogBookTest
    {
        $entity = $this->findOneBy($criteria);
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
            if ($flush === true) {
                $this->_em->flush();
            }
        }
        return $entity;
    }

    /**
     * @param LogBookCycle $cycle
     */
    public function deleteByCycle(LogBookCycle $cycle): void
    {
        //$msgRepo = $this->getEntityManager()->getRepository('App:LogBookMessage');
//        foreach ($cycle->getTests() as $test){
//            /** @var LogBookTest $test */
//            //$msgRepo->deleteByTestId($test->getId());
//            $this->delete($test);
//            $this->_em->detach($test);
//        }
        /**
         * Additional clean | optional and may be removed
         */
        $qd = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.cycle = :cycle')
            ->setParameter('cycle', $cycle->getId());
        $query = $qd->getQuery();
        $query->execute();
        $this->_em->flush();

    }

    /**
     * @param LogBookTest $test
     */
    public function delete(LogBookTest $test): void
    {
        $this->_em->remove($test);
        $this->_em->flush($test);
    }
}

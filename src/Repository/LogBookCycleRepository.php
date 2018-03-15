<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookCycleRepository extends ServiceEntityRepository
{

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookCycle::class);
    }

    /**
     * @param array $criteria
     * @return LogBookCycle
     */
    public function findOneOrCreate(array $criteria)
    {
        $entity = $this->findOneBy($criteria);

        if (null === $entity) {
            $entity = new LogBookCycle();
            $entity->setName($criteria['name']);
            $entity->setSetup($criteria['setup']);
            $entity->setUploadToken($criteria['uploadToken']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }


    public function delete(LogBookCycle $cycle)
    {
        $testRepo = $this->getEntityManager()->getRepository('App:LogBookTest');
        $testRepo->deleteByCycle($cycle);
        $this->_em->remove($cycle);
        $this->_em->flush();

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

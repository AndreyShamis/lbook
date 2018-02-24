<?php

namespace App\Repository;

use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookTestRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $_hashedData = array();

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
        if(isset(self::$_hashedData[$criteria['id']])){
            $entity = self::$_hashedData[$criteria['id']];
            $add_hash = false;
        }
        else{
            $entity = $this->findOneBy($criteria);
        }

        if (null === $entity) {
            $entity = new LogBookTest();
            $entity->setName($criteria['name']);
            //$entity->setVerdict($criteria['verdict']);
            $entity->setCycle($criteria['cycle']);
            $entity->setExecutionOrder($criteria['executionOrder']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            if($flush == true) {
                $this->_em->flush();
            }

        }
        if($add_hash) {
            self::$_hashedData[$criteria['id']] = $entity;
        }

        return $entity;
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

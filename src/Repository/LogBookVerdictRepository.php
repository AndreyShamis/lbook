<?php

namespace App\Repository;

use App\Entity\LogBookVerdict;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookVerdictRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookVerdict::class);
    }

    /**
     * @param array $criteria
     * @return LogBookVerdict
     */
    public function findOneOrCreate(array $criteria): LogBookVerdict
    {
        $criteria['name'] = strtoupper($criteria['name']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookVerdict();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
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

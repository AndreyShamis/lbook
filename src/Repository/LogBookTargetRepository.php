<?php

namespace App\Repository;

use App\Entity\LogBookTarget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

class LogBookTargetRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $hashedData = array();

    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookTarget::class);
    }

    /**
     * @param array $criteria
     * @return LogBookTarget
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOneOrCreate(array $criteria): LogBookTarget
    {
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookTarget();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }

}

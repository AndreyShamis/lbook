<?php

namespace App\Repository;

use App\Entity\LogBookTarget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookTargetRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookTarget::class);
    }

    /**
     * @param array $criteria
     * @return LogBookTarget
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

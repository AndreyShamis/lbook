<?php

namespace App\Repository;

use App\Entity\LogBookBuild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

class LogBookBuildRepository extends ServiceEntityRepository
{
    /**
     * LogBookBuildRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookBuild::class);
    }

    /**
     * @param array $criteria
     * @return LogBookBuild
     */
    public function findOneOrCreate(array $criteria): LogBookBuild
    {
        $criteria['name'] = LogBookBuild::validateName($criteria['name']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookBuild();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }
}

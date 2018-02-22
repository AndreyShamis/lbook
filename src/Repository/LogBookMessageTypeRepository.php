<?php

namespace App\Repository;

use App\Entity\LogBookMessageType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookMessageTypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookMessageType::class);
    }

    public function findOneOrCreate(array $criteria,$flush = true)
    {
        $criteria['name'] = strtoupper($criteria['name']);
        $entity = $this->findOneBy($criteria);

        if (null === $entity)
        {
            $entity = new LogBookMessageType();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            //$this->_em->clear($entity);
            if($flush == true)
            {
                $this->_em->flush();
                //$this->_em->clear($entity);
            }
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

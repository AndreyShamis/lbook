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
    protected static $_hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookTarget::class);
    }

    /**
     * @param array $criteria
     * @return LogBookTarget
     */
    public function findOneOrCreate(array $criteria)
    {
        $add_hash = true;
        if(isset(self::$_hashedData[$criteria['name']])){
            $entity = self::$_hashedData[$criteria['name']];
            $add_hash = false;
        }
        else{
            $entity = $this->findOneBy($criteria);
        }

        if (null === $entity) {
            $entity = new LogBookTarget();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        if($add_hash) {
            self::$_hashedData[$criteria['name']] = $entity;
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

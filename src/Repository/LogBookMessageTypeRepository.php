<?php

namespace App\Repository;

use App\Entity\LogBookMessageType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookMessageTypeRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $_hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookMessageType::class);
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookMessageType
     */
    public function findOneOrCreate(array $criteria, $flush = false)
    {
        $criteria['name'] = strtoupper($criteria['name']);
        $add_hash = true;
        if(isset(self::$_hashedData[$criteria['name']])){
            $entity = self::$_hashedData[$criteria['name']];
            $add_hash = false;
        }
        else{
            $entity = $this->findOneBy($criteria);
        }

        if (null === $entity) {
            $entity = new LogBookMessageType();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            //$this->_em->clear($entity);
            if($flush == true) {
                $this->_em->flush();
                //$this->_em->clear($entity);
            }

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

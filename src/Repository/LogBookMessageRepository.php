<?php

namespace App\Repository;

use App\Entity\LogBookMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookMessageRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
//    protected static $_hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookMessage::class);
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookMessage
     */
    public function findOneOrCreate(array $criteria, $flush = true)
    {
//        $add_hash = true;
//        if(isset(self::$_hashedData[$criteria['name']])){
//            $entity = self::$_hashedData[$criteria['name']];
//            $add_hash = false;
//        }
//        else{
//            $entity = $this->findOneBy($criteria);
//        }
        unset($criteria['time']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookMessage();
            $entity->setMessage($criteria['message']);
            $entity->setChain($criteria['chain']);
            $entity->setMsgType($criteria['msgType']);
            $entity->setTest($criteria['test']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            //$this->_em->clear($entity);
            if($flush == true) {
                $this->_em->flush();
                //$this->_em->clear($entity);
            }

        }
//        if($add_hash) {
//            self::$_hashedData[$criteria['name']] = $entity;
//        }

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

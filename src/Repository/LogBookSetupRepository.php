<?php

namespace App\Repository;

use App\Entity\LogBookSetup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Model\OsType;

class LogBookSetupRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $_hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookSetup::class);
    }

    /**
     * @param string $setupName
     * @return LogBookSetup|null
     */
    public function findByName(string $setupName)
    {
        if(isset(self::$_hashedData[$setupName])){
            $entity = self::$_hashedData[$setupName];
        }
        else{
            /** @var LogBookSetup $entity */
            $entity = $this->findOneBy(array("name" => $setupName));
            if($entity !== null){
                self::$_hashedData[$setupName] = $entity;
                self::$_hashedData[$entity->getId()] = $entity;
            }
        }

        return $entity;
    }

    /**
     * @param int $setupId
     * @return LogBookSetup|null
     */
    public function findById(int $setupId)
    {
        if(isset(self::$_hashedData[$setupId])){
            $entity = self::$_hashedData[$setupId];
        }
        else{
            /** @var LogBookSetup $entity */
            $entity = $this->findOneBy(array("id" => $setupId));
            if($entity !== null){
                self::$_hashedData[$setupId] = $entity;
                self::$_hashedData[$entity->getName()] = $entity;
            }
        }

        return $entity;
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookSetup
     */
    public function findOneOrCreate(array $criteria, $flush = false)
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
            $entity = new LogBookSetup();
            $entity->setName($criteria['name']);
            $entity->setCheckUpTime(false);
            $entity->setDisabled(false);
            $entity->setOwner(0);   //TODO User, Owner
            $entity->setOs(OsType::OS_UNKNOWN);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            if($flush == true) {
                $this->_em->flush();
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

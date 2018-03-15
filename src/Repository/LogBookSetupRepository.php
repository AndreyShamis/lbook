<?php

namespace App\Repository;

use App\Entity\LogBookSetup;
use App\Entity\LogBookCycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Model\OsType;

class LogBookSetupRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $hashedData = array();

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
        if (isset(self::$hashedData[$setupName])) {
            $entity = self::$hashedData[$setupName];
        } else {
            /** @var LogBookSetup $entity */
            $entity = $this->findOneBy(array("name" => $setupName));
            if($entity !== null){
                self::$hashedData[$setupName] = $entity;
                self::$hashedData[$entity->getId()] = $entity;
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
        if (isset(self::$hashedData[$setupId])) {
            $entity = self::$hashedData[$setupId];
        } else {
            /** @var LogBookSetup $entity */
            $entity = $this->findOneBy(array("id" => $setupId));
            if ($entity !== null) {
                self::$hashedData[$setupId] = $entity;
                self::$hashedData[$entity->getName()] = $entity;
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
        if (isset(self::$hashedData[$criteria['name']])) {
            $entity = self::$hashedData[$criteria['name']];
            $add_hash = false;
        } else {
            $entity = $this->findOneBy($criteria);
        }

        if (null === $entity) {
            $entity = new LogBookSetup();
            $entity->setName($criteria['name']);
            $entity->setCheckUpTime(false);
            $entity->setDisabled(false);
            $entity->setOwner(null);   //TODO User, Owner
            $entity->setOs(OsType::OS_UNKNOWN);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            if($flush == true) {
                $this->_em->flush();
            }

        }
        if ($add_hash) {
            self::$hashedData[$criteria['name']] = $entity;
        }

        return $entity;
    }

    public function delete(LogBookSetup $setup)
    {
        $cycleRepo = $this->getEntityManager()->getRepository('App:LogBookCycle');
        /** @var LogBookCycle $cycle */
        $cycles = $setup->getCycles();
        foreach ($cycles as $cycle){
            $cycleRepo->delete($cycle);
        }
        $this->_em->remove($setup);
        $this->_em->flush($setup);
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

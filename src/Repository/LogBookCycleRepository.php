<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookCycleRepository extends ServiceEntityRepository
{

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookCycle::class);
    }

    /**
     * @param array $criteria
     * @return LogBookCycle
     */
    public function findOneOrCreate(array $criteria): LogBookCycle
    {
        $entity = $this->findOneBy($criteria);
        if (! array_key_exists('uploadToken', $criteria) || $criteria['uploadToken'] === '') {
            $criteria['uploadToken'] = '9999999999999999999999999999';
        }
        if (null === $entity) {
            $entity = new LogBookCycle();
            $entity->setName($criteria['name']);
            $entity->setSetup($criteria['setup']);
            $entity->setUploadToken($criteria['uploadToken']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }

    /**
     * @param LogBookCycle $cycle
     */
    public function delete(LogBookCycle $cycle): void
    {
        $testRepo = $this->getEntityManager()->getRepository('App:LogBookTest');
        $testRepo->deleteByCycle($cycle);
        $this->_em->remove($cycle);
        $this->_em->flush();
    }
}

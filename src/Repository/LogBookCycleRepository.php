<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Utils\RandomString;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookCycleRepository extends ServiceEntityRepository
{
    /**
     * LogBookCycleRepository constructor.
     * @param RegistryInterface $registry
     */
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
        $criteria['name'] = LogBookCycle::validateName($criteria['name']);

        $entity = $this->findOneBy($criteria);
        if (! array_key_exists('uploadToken', $criteria) || $criteria['uploadToken'] === '') {
            $criteria['uploadToken'] = RandomString::generateRandomString(20);
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
     * @param string $token
     * @param LogBookSetup|null $setup
     * @return LogBookCycle|null
     */
    public function findByToken(string $token, LogBookSetup $setup=null): ?LogBookCycle
    {
        /** @var LogBookCycle $entity */
        $entity = null;

        $qb = $this->createQueryBuilder('c')
            ->where('c.uploadToken = :token')
            ->andWhere('c.tokenExpiration > CURRENT_TIMESTAMP()')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->setCacheable(false)
            ->orderBy('c.id', 'DESC');
        if ($setup !== null) {
            $qb
                ->andWhere('c.setup = :setup')
                ->setParameter('setup', $setup->getId());
        }

        $result = $qb->getQuery()->getResult();
        if (\count($result) > 0) {
            $entity = $result[0];
        }

        return $entity;
    }

    /**
     * @param LogBookCycle $cycle
     */
    public function delete(LogBookCycle $cycle): void
    {
        //print "I'm here in Cycle Repo\n";
        $testRepo = $this->getEntityManager()->getRepository('App:LogBookTest');
        $testRepo->deleteByCycle($cycle);
        $this->_em->remove($cycle);
        $this->_em->flush($cycle);
    }
}

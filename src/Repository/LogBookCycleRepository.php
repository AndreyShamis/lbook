<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Utils\RandomString;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
     * @param int $max_results
     * @return mixed
     */
    public function findByDeleteAt(int $max_results=100)
    {
        try {
            $qb = $this->createQueryBuilder('c')
                ->where('c.deleteAt <= :now')
                ->andWhere('c.keepForever = 0')
                ->setMaxResults($max_results)
                ->setParameter('now', new \DateTime('now'));
        } catch (\Exception $e) {
        }
        return $qb->getQuery()->execute();
    }

    /**
     * @param array $criteria
     * @param bool $find
     * @return LogBookCycle
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function findOneOrCreate(array $criteria, bool $find=true): LogBookCycle
    {
        $criteria['name'] = LogBookCycle::validateName($criteria['name']);
        if ($find === true) {
            $entity = $this->findOneBy($criteria);
        } else {
            $entity = null;
        }
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(LogBookCycle $cycle): void
    {
        /** @var LogBookTestRepository $testRepo */
        $testRepo = $this->getEntityManager()->getRepository('App:LogBookTest');
        $testRepo->deleteByCycle($cycle);
        try {
            $logs = realpath($cycle->getLogFilesPath());
            $finder = new Finder();
            $fileSystem = new Filesystem();
            $finder->files()->in($logs);
            foreach ($finder as $file) {
                $fileSystem->remove($file->getRealPath());
            }

        } catch (\Throwable $ex) {
            
        }

        $this->_em->remove($cycle);
        $this->_em->flush($cycle);
    }


}

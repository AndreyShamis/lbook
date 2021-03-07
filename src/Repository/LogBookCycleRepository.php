<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\SuiteExecution;
use App\Utils\RandomString;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Log\LoggerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;

class LogBookCycleRepository extends ServiceEntityRepository
{
    /** @var LoggerInterface  */
    protected $logger;
    /**
     * LogBookCycleRepository constructor.
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, LogBookCycle::class);
        $this->logger = $logger;
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
        /** @var SuiteExecutionRepository $suiteRepo */
        $suiteRepo = $this->getEntityManager()->getRepository('App:SuiteExecution');
        $logsRepo = $this->getEntityManager()->getRepository('App:LogBookMessage');
        $logsRepo->createCustomTable((string)$cycle->getId());
        $testRepo->deleteByCycle($cycle);
        $logs = $setup_path = '';
        try {

            /** @var SuiteExecution $suite */
            $suite = $suiteRepo->findOneBy(['cycle' => $cycle], null);
            if ($suite !== null) {
                $suite->setCycle(null);
                $this->_em->persist($suite);
            }
        } catch (\Throwable $ex) {
            $this->logger->critical('[CYCLE][DELETE]: $suiteRepo Throwable CYCLE ID:' . $cycle->getId(),
                array(
                    $ex->getMessage(),
                    $ex,
                    $logs,
                    $setup_path));
        }
        try {
            $fileSystem = new Filesystem();
            $setup_path = $cycle->getSetup()->getLogFilesPath();
            $logs = $cycle->getLogFilesPath();
            /** This validation required in case that cycle path is /SOME_NUMBER only */
            if ($setup_path !== '' && mb_strlen($logs) > mb_strlen($setup_path) && mb_strpos($logs, $setup_path) !== false) {
                if ($fileSystem->exists($logs)) {
                    $fileSystem->remove($logs);
                }
            }

        } catch (\Throwable $ex) {
            $this->logger->critical('[CYCLE][DELETE]: Throwable CYCLE ID:' . $cycle->getId(),
                array(
                    $ex->getMessage(),
                    $ex,
                    $logs,
                    $setup_path));
        }
        $this->logger->notice('[CYCLE][DELETE]: Removing CYCLE '. $cycle->getId());
        $this->_em->remove($cycle);
        $this->_em->flush($cycle);
    }


}

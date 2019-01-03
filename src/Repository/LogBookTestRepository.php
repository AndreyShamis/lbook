<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Filesystem\Filesystem;

class LogBookTestRepository extends ServiceEntityRepository
{
    /** @var LoggerInterface  */
    protected $logger;

    /**
     * LogBookTestRepository constructor.
     * @param RegistryInterface $registry
     * @param LoggerInterface $logger
     */
    public function __construct(RegistryInterface $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, LogBookTest::class);
        $this->logger = $logger;
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookTest
     * @throws ORMException
     */
    public function findOneOrCreate(array $criteria, $flush = false): LogBookTest
    {
        $criteria['name'] = LogBookTest::validateName($criteria['name']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookTest();
            $entity->setName($criteria['name']);
            //$entity->setVerdict($criteria['verdict']);
            $entity->setCycle($criteria['cycle']);
            $entity->setLogFile($criteria['logFile']);
            $entity->setLogFileSize($criteria['logFileSize']);
            $entity->setExecutionOrder($criteria['executionOrder']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            if ($flush === true) {
                $this->_em->flush();
            }
        }
        return $entity;
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookTest
     * @throws ORMException|UniqueConstraintViolationException
     */
    public function create(array $criteria, $flush = false): ?LogBookTest
    {
        $criteria['name'] = LogBookTest::validateName($criteria['name']);
        $entity = new LogBookTest();
        $entity->setName($criteria['name']);
        //$entity->setVerdict($criteria['verdict']);
        $entity->setCycle($criteria['cycle']);
        $entity->setLogFile($criteria['logFile']);
        $entity->setLogFileSize($criteria['logFileSize']);
        $entity->setExecutionOrder($criteria['executionOrder']);
        $this->_em->persist($entity);
        $this->_em->flush($entity);
        if ($flush === true) {
            $this->_em->flush();
        }

        return $entity;
    }

    /**
     * @param LogBookCycle $cycle
     */
    public function deleteByCycle(LogBookCycle $cycle): void
    {
        //$msgRepo = $this->getEntityManager()->getRepository('App:LogBookMessage');
//        foreach ($cycle->getTests() as $test){
//            /** @var LogBookTest $test */
//            //$msgRepo->deleteByTestId($test->getId());
//            $this->delete($test);
//            $this->_em->detach($test);
//        }
        /**
         * Additional clean | optional and may be removed
         */
        $qd = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.cycle = :cycle')
            ->setParameter('cycle', $cycle->getId());
        $query = $qd->getQuery();
        $query->execute();
    }

    /**
     * @param LogBookTest $test
     * @throws ORMException
     */
    public function delete(LogBookTest $test): void
    {
        $fileName = '';
        try {
            $fileSystem = new Filesystem();
            $fileName = $test->getLogFilesPath();
            if ($fileSystem->exists($fileName) and is_file($fileName)) {
                $this->logger->info('[TEST][DELETE]: Remove log file [' . $fileName . '] for TEST ID:' . $test->getId());
                $fileSystem->remove($fileName);
            } else {
                $this->logger->critical('[TEST][DELETE]: FILE_NOT_EXIST [' . $fileName . '] for TEST ID:' . $test->getId());
            }
        } catch (\Throwable $ex) {
            $this->logger->critical('[TEST][DELETE]: Throwable TEST ID:' . $test->getId(),
                array(
                    $ex->getMessage(),
                    $ex, $test->getName(),
                    $fileName));
        }
        $this->_em->remove($test);
        $this->_em->flush($test);
    }
}

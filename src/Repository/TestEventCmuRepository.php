<?php

namespace App\Repository;

use App\Entity\SuiteExecution;
use App\Entity\TestEventCmu;
use DateInterval;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TestEventCmu|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestEventCmu|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestEventCmu[]    findAll()
 * @method TestEventCmu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestEventCmuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestEventCmu::class);
    }

    /**
     * @param array $criteria
     * @return TestEventCmu
     * @throws ORMException
     */
    public function findOneOrCreate(array $criteria): TestEventCmu
    {
        $entity = $this->findOneBy([
            'test' => $criteria['test'],
            'block' => $criteria['block'],
            'fault' => $criteria['fault'],
            'a_time' => $criteria['a_time'],
            'b_time' => $criteria['b_time'],
        ]);
        if (null === $entity) {
            $entity = new TestEventCmu();
            $entity->setBlock($criteria['block']);
            $entity->setFault($criteria['fault']);
            $entity->setAValue($criteria['a_value']);
            $entity->setBValue($criteria['b_value']);
            $entity->setATime($criteria['a_time']);
            $entity->setBTime($criteria['b_time']);
            $entity->setTest($criteria['test']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }



    /**
     * @return string[]|null
     */
    public function getUniqBlockNames()
    {
        $reset = function ($input) {
            return $input['block'];
        };
        $ret = $this->createQueryBuilder('s')
            ->select('s.block')->distinct()
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(200000)
            ->setLifetime(7200)
            ->setCacheable(true);

        $ret = $ret->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
        if (count($ret) > 0) {
            $map = array_map($reset, $ret);
            $ret = array_combine($map, $map);
            sort($ret);
            return $ret;
        }
        return null;
    }

    /**
     * @param string $block
     * @param $day
     * @return \Doctrine\ORM\QueryBuilder
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countBlockInDay(string $block, $day)
    {
        $t1 = new \DateTime($day);
        $t2 = new \DateTime($day);
//        $di1 = new \DateInterval('P1D');
        $di2 = new \DateInterval('P1D');
//        $di1->invert = true;

        $day1 = $t1->add(DateInterval::createFromDateString('-1 days'));//$t->format('Y-m-d');
        $day2 = $t2->add($di2);//$t->format('Y-m-d');
        $ret = $this->createQueryBuilder('s')
            ->select('count(s.block)')
            ->where('s.block = :block')
            ->andWhere('s.createdAt >= :day1')
            ->andWhere('s.createdAt <= :day2')
        ->setParameters([
            'block' => $block,
            'day1' => $day1,
            'day2' => $day2 ,
        ]);
        //$ret = $ret->getQuery()->getResult();
        return $ret->getQuery()->getSingleScalarResult();
    }
    // /**
    //  * @return TestEventCmu[] Returns an array of TestEventCmu objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TestEventCmu
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

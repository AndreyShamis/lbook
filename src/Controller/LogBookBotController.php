<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSetupRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class LogBookBotController
 * @package App\Controller
 * @Route("bot")
 */
class LogBookBotController extends Controller
{
    /**
     * @Route("/delete_cycles", name="bot_delete_cycles")
     * @param LogBookCycleRepository $cycleRepo
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \InvalidArgumentException
     */
    public function deleteCycle(LogBookCycleRepository $cycleRepo): Response
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('cycleDelete');
        $qd = $cycleRepo->createQueryBuilder('c')
            ->where('c.forDelete = :for_delete')
            ->setMaxResults(1)
            ->setParameter('for_delete', 1);
        $query = $qd->getQuery();
        $query->execute();
        $cycles = $query->getResult();
        $responseContent = '';
        /** @var LogBookCycle $cycle */
        foreach ((array) $cycles as $cycle) {
            $cycleName = $cycle->getName();
            $cycleId = $cycle->getId();
            $testsFound = $cycle->getTests()->count();
            $responseContent = sprintf('%sRemoving %s:[%d] - tests=%d %s',$responseContent,  $cycleName, $cycleId, $testsFound, "\n");
            $cycleRepo->delete($cycle);
        }

        if ($responseContent === '') {
            $responseContent = "Nothing found for delete\n";
        }
        $event = $stopwatch->stop('cycleDelete');
        $time = new \DateTime();
        $responseContent = sprintf('%s | Duration %d(milliseconds) %s%s', $time->format('Y/m/d H:i:s') , $event->getDuration(), '| ', $responseContent);
        return new Response($responseContent);
    }

    /**
     * @Route("/delete_cycles_retention", name="bot_delete_cycles_retention")
     * @param LogBookCycleRepository $cycleRepo
     * @param LogBookSetupRepository $setupRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteCycleRetention(LogBookCycleRepository $cycleRepo, LogBookSetupRepository $setupRepo): Response
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('deleteCycleRetention');
        $em = $this->getDoctrine()->getManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('target_date', 'target_date');
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('name', 'name');
        /** @var \Doctrine\ORM\NativeQuery $setups_nq */
        $setups_nq = $em->createNativeQuery('SELECT *, (:target_date) as target_date FROM lbook_setups s WHERE s.updated_at < (NOW()-INTERVAL s.retention_policy DAY) AND s.is_disabled = 0', $rsm);
        $setups_nq->setParameter(':target_date', 'NOW()-INTERVAL s.retention_policy DAY');
        //$setups_nnq = $setupRepo->createNativeNamedQuery('s');
        //$qd_setups = $setupRepo->createNativeNamedQuery()
//            ->where('s.updatedAt < :this_day')
//            //->where('s.updatedAt < :this_day')
//            //->where('(s.updated_at > (s.updated_at + INTERVAL s.retention_policy DAY))')
//            ->andWhere('s.disabled = 0')
//            //->where('(s.updatedAt > (s.updatedAt + INTERVAL s.retentionPolicy DAY))')
//            //->andWhere('s.disabled = 0')
//            //->setParameter(':now',  new \DateTime('now'))
//            ->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')
            //->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')

//
//        $qd_setups = $setupRepo->createQueryBuilder('s')
//            ->where('s.updatedAt < :this_day')
//            //->where('s.updatedAt < :this_day')
//            //->where('(s.updated_at > (s.updated_at + INTERVAL s.retention_policy DAY))')
//            ->andWhere('s.disabled = 0')
//            //->where('(s.updatedAt > (s.updatedAt + INTERVAL s.retentionPolicy DAY))')
//            //->andWhere('s.disabled = 0')
//            //->setParameter(':now',  new \DateTime('now'))
//            ->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')
//            //->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')
//            ->setMaxResults(100);
//        $query_setups = $qd_setups->getQuery();
//        $query_setups->execute();
        $setups = $setups_nq->getResult();
//
//        echo $query_setups->getSQL() . "<br/>";
//        echo $query_setups->getDQL() . "<br/>";
//        echo "<pre>";
//        print_r($query_setups->getParameters());
//        $setups = $query_setups->getResult(); // \Doctrine\ORM\Query::HYDRATE_ARRAY
        //print_r($setups);
        echo "Cou8nt: " . count($setups) . "<br/>";
        echo "</pre>";
//        try {
//            foreach ( $setups as $setup) {
//                echo "Setup " . $setup->getId() . ' ' . $setup->getName() . "<br/>";
//            }
//        } catch (\Exception $ex) { }

        try {
            foreach ( $setups as $setup) {
                echo "Setup " . $setup['id'] . ' ' . $setup['name'] . ' ' . $setup['target_date'] . "<br/>";
            }
        } catch (\Exception $ex) { }

        exit();
        $qd = $cycleRepo->createQueryBuilder('c')
            ->where('c.keepForever = :keep_forever')
            ->andWhere('c.createdAt > :created_at')
            ->setMaxResults(1)
            ->setParameter('keep_forever', 0)
            ->setParameter('created_at', 'c.createdAt + ');
        $query = $qd->getQuery();
        $query->execute();
        $cycles = $query->getResult();
        $responseContent = '';
        /** @var LogBookCycle $cycle */
        foreach ((array) $cycles as $cycle) {
            $cycleName = $cycle->getName();
            $cycleId = $cycle->getId();
            $testsFound = $cycle->getTests()->count();
            $responseContent = sprintf('%sRemoving %s:[%d] - tests=%d %s',$responseContent,  $cycleName, $cycleId, $testsFound, "\n");
            $cycleRepo->delete($cycle);
        }

        if ($responseContent === '') {
            $responseContent = "Nothing found for delete\n";
        }
        $event = $stopwatch->stop('deleteCycleRetention');
        $time = new \DateTime();
        $responseContent = sprintf('%s | Duration %d(milliseconds) %s%s', $time->format('Y/m/d H:i:s') , $event->getDuration(), '| ', $responseContent);
        return new Response($responseContent);
    }
}

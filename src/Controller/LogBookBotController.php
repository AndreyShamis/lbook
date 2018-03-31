<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Repository\LogBookCycleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
            $responseContent .= 'Removing ' . $cycleName  . ':[' .$cycleId. "]\n";
            $cycleRepo->delete($cycle);
        }

        if ($responseContent === '') {
            $responseContent = "Nothing found for delete\n";
        }
        return new Response($responseContent);
    }
}

<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 17/02/18
 * Time: 08:28
 */

namespace App\Controller;

use App\Repository\SuiteExecutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class LogBookDefaultController extends AbstractController
{

    /**
     * Lists all test entities.
     *
     * @Route("/", name="home_index", methods={"GET"})
     * @param SuiteExecutionRepository $suites
     * @return Response
     */
    public function index(SuiteExecutionRepository $suites): Response
    {
        return $this->redirectToRoute('show_first_favorite');
//        $my_suites = $suites->findSuitesInProgress();
//        $sanity = $suites->findSanitySuitesInProgress();
//        $integration = $suites->findIntegrationSuitesInProgress();
//        $nightly = $suites->findNightlySuitesInProgress();
//        $weekly = $suites->findWeeklySuitesInProgress();
//
//        return $this->render('lbook/default/index.html.twig',
//            [
//                'suites' => $my_suites,
//                'sanity' => $sanity,
//                'integration' => $integration,
//                'nightly' => $nightly,
//                'weekly' => $weekly,
//            ]);
    }

    /**
     * @Route("/send_email", name="send_email_example")
     * @param \Swift_Mailer $mailer
     * @return Response
     * @throws \LogicException
     */
    public function emailExample(\Swift_Mailer $mailer): Response
    {

        $message = (new \Swift_Message('Hello Email'))
            ->setFrom('hicam.golda@gmail.com')
            ->setTo('lolnik@gmail.com')
            ->setBody(
                $this->renderView(
                // templates/emails/registration.html.twig
                    'lbook/email/test.html.twig',
                    array('name' => 'Test Name')
                ),
                'text/html'
            )
            /*
             * If you also want to include a plaintext version of the message
            ->addPart(
                $this->renderView(
                    'emails/registration.txt.twig',
                    array('name' => $name)
                ),
                'text/plain'
            )
            */
        ;
        $mailer->send($message);
        return $this->index();
    }
}
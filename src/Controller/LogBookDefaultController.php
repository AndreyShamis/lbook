<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 17/02/18
 * Time: 08:28
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class LogBookDefaultController extends AbstractController
{

    /**
     * Lists all test entities.
     *
     * @Route("/", name="home_index", methods={"GET"})
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function index(): Response
    {
        $em = $this->getDoctrine()->getManager();
        return $this->render('lbook/default/index.html.twig', array());
    }

    /**
     * @Route("/send_email", name="send_email_example")
     * @param \Swift_Mailer $mailer
     * @return \Symfony\Component\HttpFoundation\Response
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
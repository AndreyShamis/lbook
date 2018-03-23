<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 17/02/18
 * Time: 08:28
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class LogBookDefaultController extends Controller
{

    /**
     * Lists all test entities.
     *
     * @Route("/", name="home_index")
     * @Method("GET")
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function index(): Response
    {
        $em = $this->getDoctrine()->getManager();
        $setups = $em->getRepository('App:LogBookSetup')->findAll();
        return $this->render('lbook/default/index.html.twig', array(
            'setups' => $setups,
        ));
    }

    /**
     * @Route("/send_email", name="send_email_example")
     * @param \Swift_Mailer $mailer
     * @return \Symfony\Component\HttpFoundation\Response
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
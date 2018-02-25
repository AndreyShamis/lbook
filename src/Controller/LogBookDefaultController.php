<?php
/**
 * Created by PhpStorm.
 * User: Andrey Shamis
 * Date: 17/02/18
 * Time: 08:28
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class LogBookDefaultController extends Controller
{

    /**
     * Lists all test entities.
     *
     * @Route("/", name="logbook_index")
     * @Method("GET")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $cycles = $em->getRepository('App:LogBookCycle')->findAll();
        $setups = $em->getRepository('App:LogBookSetup')->findAll();

        return $this->render('lbook/default/index.html.twig', array(
            'cycles' => $cycles,
            'setups' => $setups,
        ));
    }
}
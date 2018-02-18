<?php

namespace App\Werd\LbookBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name="No name")
    {
        return $this->render('@Lbook/Default/index.html.twig', array('name' => $name));
    }
}

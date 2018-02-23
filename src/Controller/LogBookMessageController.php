<?php

namespace App\Controller;

use App\Entity\LogBookMessageType;
use App\Entity\LogBookMessage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;


/**
 * Uploader controller.
 *
 * @Route("log")
 */
class LogBookMessageController extends Controller
{
    /**
     * @Route("/", name="log_index")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $messages = $em->getRepository('App:LogBookMessage')->findAll();

        return $this->render('lbook/log/index.html.twig', array(
            'logs' => $messages,
        ));
        // replace this line with your own code!
        //return $this->render('@Maker/demoPage.html.twig', [ 'path' => str_replace($this->getParameter('kernel.project_dir').'/', '', __FILE__) ]);
    }

    /**
     * Creates a new Log/Message entity.
     *
     * @Route("/new", name="log_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookMessage();
        $form = $this->createForm('App\Form\LogBookMessageType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('log_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/log/new.html.twig', array(
            'log' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Log/Message entity.
     *
     * @Route("/{id}", name="log_show")
     * @Method("GET")
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookMessage $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/log/show.html.twig', array(
            'log' => $obj,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Log/Message entity.
     *
     * @Route("/{id}/edit", name="log_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookMessage $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);
        $editForm = $this->createForm('App\Form\LogBookMessageType', $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('log_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/log/edit.html.twig', array(
            'log' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Log / Message entity.
     *
     * @Route("/{id}", name="log_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LogBookMessage $obj)
    {
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('log_index');
    }

    /**
     * Creates a form to delete a Log/ Message entity.
     *
     * @param LogBookMessage $obj The verdict entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LogBookMessage $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('log_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}

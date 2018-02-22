<?php

namespace App\Controller;

use App\Form\LogBookMessageTypeType;
use App\Entity\LogBookMessageType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Uploader controller.
 *
 * @Route("msg_type")
 */
class LogBookMessageTypeController extends Controller
{
    /**
     * @Route("/", name="msg_type_index")
     * @Method("GET")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $msg_types = $em->getRepository('App:LogBookMessageType')->findAll();

        return $this->render('lbook/msg_type/index.html.twig', array(
            'msg_types' => $msg_types,
        ));
        // replace this line with your own code!
        //return $this->render('@Maker/demoPage.html.twig', [ 'path' => str_replace($this->getParameter('kernel.project_dir').'/', '', __FILE__) ]);
    }

    /**
     * Creates a new verdict entity.
     *
     * @Route("/new", name="msg_type_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookMessageType();
        $form = $this->createForm('App\Form\LogBookMessageTypeType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('msg_type_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/msg_type/new.html.twig', array(
            'msg_type' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a verdict entity.
     *
     * @Route("/{id}", name="msg_type_show")
     * @Method("GET")
     * @param LogBookMessageType $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookMessageType $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/msg_type/show.html.twig', array(
            'msg_type' => $obj,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing verdict entity.
     *
     * @Route("/{id}/edit", name="msg_type_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookMessageType $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookMessageType $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);
        $editForm = $this->createForm('App\Form\LogBookMessageTypeType', $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('msg_type_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/msg_type/edit.html.twig', array(
            'msg_type' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a verdict entity.
     *
     * @Route("/{id}", name="msg_type_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookMessageType $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LogBookMessageType $obj)
    {
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('msg_type_index');
    }

    /**
     * Creates a form to delete a verdict entity.
     *
     * @param LogBookMessageType $obj The verdict entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LogBookMessageType $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('msg_type_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}

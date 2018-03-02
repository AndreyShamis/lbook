<?php

namespace App\Controller;

use App\Entity\LogBookSetup;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setup controller.
 *
 * @Route("setup")
 */
class LogBookSetupController extends Controller
{

    /**
     * Lists all setup entities.
     *
     * @Route("/", name="setup_index")
     * @Method("GET")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $setups = $em->getRepository('App:LogBookSetup')->findAll();

        return $this->render('lbook/setup/index.html.twig', array(
            'setups' => $setups,
        ));
    }

    /**
     * Creates a new setup entity.
     *
     * @Route("/new", name="setup_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookSetup();
        $form = $this->createForm('App\Form\LogBookSetupType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('setup_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/setup/new.html.twig', array(
            'test' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a setup entity.
     *
     * @Route("/{id}", name="setup_show")
     * @Method("GET")
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookSetup $obj)
    {
        $user= $this->get('security.token_storage')->getToken()->getUser();
        /** @var PersistentCollection $moderators */
        $moderators = $obj->getModerators();
        //if(in_array($user, $moderators)){
        if($moderators->contains($user)){
            $deleteForm = $this->createDeleteForm($obj)->createView();
        }
        else{
            $deleteForm = null;
        }

        return $this->render('lbook/setup/show.html.twig', array(
            'setup' => $obj,
            'delete_form' => $deleteForm,
        ));
    }

    /**
     * Displays a form to edit an existing setup entity.
     *
     * @Route("/{id}/edit", name="setup_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookSetup $obj)
    {
        $user= $this->get('security.token_storage')->getToken()->getUser();

        // check for "edit" access: calls all voters
        $this->denyAccessUnlessGranted('edit', $obj);
        /** @var PersistentCollection $moderators */
        $moderators = $obj->getModerators();
        //if(in_array($user, $moderators)){
        if($moderators->contains($user)){
            $deleteForm = $this->createDeleteForm($obj)->createView();
        }
        else{
            $deleteForm = null;
        }


        $editForm = $this->createForm('App\Form\LogBookSetupType', $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('setup_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/setup/edit.html.twig', array(
            'setup' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm,
        ));
    }

    /**
     * Deletes a setup entity.
     *
     * @Route("/{id}", name="setup_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LogBookSetup $obj)
    {
        $this->denyAccessUnlessGranted('delete', $obj);
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('setup_index');
    }

    /**
     * Creates a form to delete a setup entity.
     *
     * @param LogBookSetup $obj The test entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookSetup $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('setup_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}

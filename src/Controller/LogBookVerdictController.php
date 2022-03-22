<?php

namespace App\Controller;

use App\Entity\LogBookVerdict;
use App\Form\LogBookVerdictType;
use App\Repository\LogBookVerdictRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verdict controller.
 *
 * @Route("verdict")
 */
class LogBookVerdictController extends AbstractController
{
    /**
     * Lists all verdict entities.
     *
     * @Route("/", name="verdict_index")
     * @param LogBookVerdictRepository $logBookVerdictRepository
     * @return Response
     */
    public function index(LogBookVerdictRepository $logBookVerdictRepository): Response
    {
        return $this->render('lbook/verdict/index.html.twig', ['verdicts' => $logBookVerdictRepository->findAll()]);
    }

    /**
     * Creates a new verdict entity.
     *
     * @Route("/new", name="verdict_new", methods={"GET|POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException|\LogicException
     */
    public function newAction(Request $request, ManagerRegistry $doctrine)
    {
        $obj = new LogBookVerdict();
        $form = $this->createForm(LogBookVerdictType::class, $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('verdict_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/verdict/new.html.twig', array(
            'verdict' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a verdict entity.
     *
     * @Route("/{id}", name="verdict_show", methods={"GET"})
     * @param LogBookVerdict $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookVerdict $obj): Response
    {
        $this->denyAccessUnlessGranted('view', $obj);
        $deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/verdict/show.html.twig', array(
            'verdict' => $obj,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing verdict entity.
     *
     * @Route("/{id}/edit", name="verdict_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookVerdict $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException|\LogicException
     */
    public function editAction(Request $request, LogBookVerdict $obj, ManagerRegistry $doctrine)
    {
        $this->denyAccessUnlessGranted('edit', $obj);
        $deleteForm = $this->createDeleteForm($obj);
        $editForm = $this->createForm(LogBookVerdictType::class, $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('verdict_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/verdict/edit.html.twig', array(
            'verdict' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a verdict entity.
     *
     * @Route("/{id}", name="verdict_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookVerdict $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\Form\Exception\LogicException|\LogicException
     */
    public function deleteAction(Request $request, LogBookVerdict $obj, ManagerRegistry $doctrine): RedirectResponse
    {
        $this->denyAccessUnlessGranted('delete', $obj);
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('verdict_index');
    }

    /**
     * Creates a form to delete a verdict entity.
     *
     * @param LogBookVerdict $obj The verdict entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookVerdict $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('verdict_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}

<?php

namespace App\Controller;

use App\Entity\StorageString;
use App\Form\StorageStringType;
use App\Repository\StorageStringRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/storage/string")
 */
class StorageStringController extends AbstractController
{
    /**
     * @Route("/", name="storage_string_index", methods={"GET"})
     */
    public function index(StorageStringRepository $storageStringRepository): Response
    {
        return $this->render('storage_string/index.html.twig', [
            'storage_strings' => $storageStringRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="storage_string_new", methods={"GET","POST"})
     */
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $storageString = new StorageString();
        $form = $this->createForm(StorageStringType::class, $storageString);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($storageString);
            $entityManager->flush();

            return $this->redirectToRoute('storage_string_index');
        }

        return $this->render('storage_string/new.html.twig', [
            'storage_string' => $storageString,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="storage_string_show", methods={"GET"})
     */
    public function show(StorageString $storageString): Response
    {
        return $this->render('storage_string/show.html.twig', [
            'storage_string' => $storageString,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="storage_string_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, StorageString $storageString, ManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(StorageStringType::class, $storageString);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('storage_string_index');
        }

        return $this->render('storage_string/edit.html.twig', [
            'storage_string' => $storageString,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="storage_string_delete", methods={"DELETE"})
     */
    public function delete(Request $request, StorageString $storageString, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('delete'.$storageString->getId(), $request->request->get('_token'))) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($storageString);
            $entityManager->flush();
        }

        return $this->redirectToRoute('storage_string_index');
    }
}

<?php

namespace App\Controller;

use App\Entity\LogBookUser;
use App\Entity\LogBookUserSettings;
use App\Form\LogBookUserSettingsType;
use App\Repository\LogBookUserSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user_settings")
 */
class LogBookUserSettingsController extends Controller
{
    /**
     * @Route("/", name="user_settings_index", methods="GET")
     * @param LogBookUserSettingsRepository $logBookUserSettingsRepository
     * @return Response
     */
    public function index(LogBookUserSettingsRepository $logBookUserSettingsRepository): Response
    {
        return $this->render('lbook/user_settings/index.html.twig', ['user_settings' => $logBookUserSettingsRepository->findAll()]);
    }

    /**
     * @Route("/{id}", name="user_settings_show", methods="GET")
     * @param LogBookUser $logBookUser
     * @return Response
     */
    public function show(LogBookUser $logBookUser): Response
    {
        $settings = $logBookUser->getSettings();
        if ($settings === null) {
            $userSettings = new LogBookUserSettings();
            $logBookUser->setSettings($userSettings);
            $em = $this->getDoctrine()->getManager();
            $em->persist($logBookUser);
            $em->flush();
            $settings = $logBookUser->getSettings();
        }
        return $this->render('lbook/user_settings/show.html.twig', ['log_book_user_setting' => $settings]);
    }

    /**
     * @Route("/{id}/edit", name="user_settings_edit", methods="GET|POST")
     * @param Request $request
     * @param LogBookUserSettings $logBookUserSetting
     * @return Response
     */
    public function edit(Request $request, LogBookUserSettings $logBookUserSetting): Response
    {
        $form = $this->createForm(LogBookUserSettingsType::class, $logBookUserSetting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_settings_edit', ['id' => $logBookUserSetting->getId()]);
        }

        return $this->render('lbook/user_settings/edit.html.twig', [
            'log_book_user_setting' => $logBookUserSetting,
            'form' => $form->createView(),
        ]);
    }

}

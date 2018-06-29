<?php

namespace App\Controller;

use App\Entity\LogBookUser;
use App\Entity\LogBookUserSettings;
use App\Form\LogBookUserSettingsType;
use App\Repository\LogBookUserSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
     * @param LogBookUser $logBookUser
     * @return LogBookUserSettings
     */
    protected function validateAndCreateSettings(LogBookUser $logBookUser): LogBookUserSettings
    {
        $settings = $logBookUser->getSettings();
        if ($settings === null || $settings->getId() === null) {
            $em = $this->getDoctrine()->getManager();
            $userSettings = new LogBookUserSettings();
            $em->persist($userSettings);
            $logBookUser->setSettings($userSettings);
            $em->persist($logBookUser);
            $em->flush();
            $settings = $logBookUser->getSettings();
        }
        return $settings;
    }

    /**
     * @Route("/{id}", name="user_settings_show", methods="GET")
     * @param LogBookUser $logBookUser
     * @return Response
     */
    public function show(LogBookUser $logBookUser): Response
    {
        $settings = $this->validateAndCreateSettings($logBookUser);
        return $this->render('lbook/user_settings/show.html.twig',
            [
                'log_book_user_setting' => $settings,
                'user' => $logBookUser
            ]);
    }

    /**
     * @Route("/{id}/edit", name="user_settings_edit", methods="GET|POST")
     * @param Request $request
     * @param LogBookUser $logBookUser
     * @return Response
     */
    public function edit(Request $request, LogBookUser $logBookUser): Response
    {
        $settings = $this->validateAndCreateSettings($logBookUser);

        $form = $this->createForm(LogBookUserSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_settings_edit', ['id' => $logBookUser->getId()]);
        }

        return $this->render('lbook/user_settings/edit.html.twig', [
            'log_book_user_setting' => $settings,
            'user' => $logBookUser,
            'form' => $form->createView(),
        ]);
    }

}

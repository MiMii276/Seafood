<?php
// src/Controller/GoogleController.php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect([], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(Request $request): Response
    {
        return $this->redirectToRoute('app_dashboard');
    }
}
<?php

namespace App\Controller;

use App\Service\ActivityLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends AbstractController
{
    public function __construct(private ActivityLogger $activityLogger) {}

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If already logged in, go to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the firewall.');
    }

    // ADD THIS - FORCED LOGOUT THAT REALLY WORKS
    #[Route('/force-logout', name: 'app_force_logout')]
    public function forceLogout(): Response
    {
        // Force logout by clearing security token
        $this->container->get('security.token_storage')->setToken(null);
        
        // Invalidate the session
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request) {
            $request->getSession()->invalidate();
        }
        
        // Redirect to homepage
        return $this->redirectToRoute('app_home');
    }
}
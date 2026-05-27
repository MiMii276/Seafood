<?php

namespace App\Controller;

use App\Service\ActivityLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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

        // Kept as lowercase to match the standard Linux fix we did earlier!
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

    // FIXED LOGOUT - Using proper Dependency Injection
    #[Route('/force-logout', name: 'app_force_logout')]
    public function forceLogout(TokenStorageInterface $tokenStorage, RequestStack $requestStack): Response
    {
        // Force logout safely by clearing security token
        $tokenStorage->setToken(null);
        
        // Invalidate the session safely
        $session = $requestStack->getSession();
        if ($session) {
            $session->invalidate();
        }
        
        // Redirect to homepage (Make sure 'app_home' or 'app_login' exists in your routes!)
        return $this->redirectToRoute('app_login');
    }
}
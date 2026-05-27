<?php

namespace App\Security;

use App\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private ActivityLogger $activityLogger
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();
                
                // Only allow specific email domains for staff
                $allowedDomains = ['seafoodie.com', 'admin.com', 'staff.com', 'gmail.com']; // Add your domains
                $emailDomain = substr(strrchr($email, "@"), 1);
                
                if (!in_array($emailDomain, $allowedDomains)) {
                    throw new AuthenticationException('Only staff members can login with Google.');
                }
                
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                
                if (!$user) {
                    // Create new user as STAFF
                    $user = new User();
                    $user->setEmail($email);
                    $user->setName($googleUser->getName());
                    $user->setPassword('');
                    $user->setIsActive(true);
                    $user->setIsVerified(true);
                    $user->setVerifiedAt(new \DateTimeImmutable());
                    $user->setRoles(['ROLE_STAFF']);
                    $user->setGoogleId($googleUser->getId());
                    
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    
                    $this->activityLogger->log($user, 'GOOGLE_REGISTER', 'Staff registered via Google: ' . $email);
                } else {
                    // Update existing user
                    if (!$user->isVerified()) {
                        $user->setIsVerified(true);
                        $user->setVerifiedAt(new \DateTimeImmutable());
                        $this->entityManager->flush();
                    }
                    if (!$user->getGoogleId()) {
                        $user->setGoogleId($googleUser->getId());
                        $this->entityManager->flush();
                    }
                    
                    // ENSURE they have STAFF role
                    $roles = $user->getRoles();
                    if (!in_array('ROLE_STAFF', $roles)) {
                        $user->setRoles(['ROLE_STAFF']);
                        $this->entityManager->flush();
                    }
                    
                    $this->activityLogger->log($user, 'GOOGLE_LOGIN', 'Staff logged in via Google: ' . $email);
                }
                
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        
        // Check if user has STAFF role
        if (in_array('ROLE_STAFF', $user->getRoles())) {
            // Redirect to STAFF dashboard
            return new RedirectResponse($this->router->generate('app_dashboard'));
        }
        
        // Default redirect for other roles
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add('error', $exception->getMessage());
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
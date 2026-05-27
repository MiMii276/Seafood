<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        EmailVerificationService $verificationService,
        ActivityLogger $activityLogger
    ): Response {
        // If already logged in, go to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $name = $request->request->get('name');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $role = $request->request->get('role', 'ROLE_USER');

            // Validate inputs
            $errors = [];
            
            if (empty($email)) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }

            // Validate role
            $allowedRoles = ['ROLE_USER', 'ROLE_STAFF', 'ROLE_ADMIN'];
            if (!in_array($role, $allowedRoles)) {
                $role = 'ROLE_USER';
            }

            // Check if email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $errors[] = 'Email already registered. Please login instead.';
            }

            // If no errors, create user
            if (empty($errors)) {
                try {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setRoles([$role]);
                    $user->setIsActive(true);
                    $user->setIsVerified(false); // NOT verified yet
                    
                    // Generate verification token
                    $user->setVerificationToken(bin2hex(random_bytes(32)));
                    
                    // Hash the password
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                    
                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    // Send verification email
                    $verificationService->sendVerificationEmail($user);
                    
                    // Log the registration
                    $activityLogger->log('REGISTER', 'User registered: ' . $email . ' - Verification email sent');
                    
                    // Get role display name for success message
                    $roleName = match($role) {
                        'ROLE_ADMIN' => 'Admin',
                        'ROLE_STAFF' => 'Staff',
                        default => 'User'
                    };
                    
                    $this->addFlash('success', "Registration successful! You registered as a $roleName. Please check your email to verify your account before logging in.");
                    return $this->redirectToRoute('app_login');
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Registration failed: ' . $e->getMessage());
                }
            }

            // If errors, show them
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('registration/register.html.twig');
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        EmailVerificationService $verificationService,
        ActivityLogger $activityLogger,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $verificationService->verifyEmail($token);
        
        if ($user) {
            $activityLogger->log('EMAIL_VERIFIED', 'User verified email: ' . $user->getEmail());
            $this->addFlash('success', '✅ Email verified successfully! You can now log in.');
            
            // If user is staff/admin, log special message
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $this->addFlash('info', 'Welcome Admin! You now have full access to the system.');
            } elseif (in_array('ROLE_STAFF', $user->getRoles())) {
                $this->addFlash('info', 'Welcome Staff! You can now manage products and catering.');
            }
        } else {
            $this->addFlash('error', '❌ Invalid or expired verification token. Please request a new verification email.');
        }
        
        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        EmailVerificationService $verificationService,
        EntityManagerInterface $entityManager,
        ActivityLogger $activityLogger
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if (!$user) {
                $this->addFlash('error', 'No account found with that email address.');
                return $this->redirectToRoute('app_login');
            }
            
            if ($user->isVerified()) {
                $this->addFlash('info', 'This email is already verified. You can log in directly.');
                return $this->redirectToRoute('app_login');
            }
            
            // Generate new token
            $user->setVerificationToken(bin2hex(random_bytes(32)));
            $entityManager->flush();
            
            // Resend verification email
            $verificationService->sendVerificationEmail($user);
            $activityLogger->log('RESEND_VERIFICATION', 'Resent verification email to: ' . $email);
            
            $this->addFlash('success', '📧 Verification email resent! Please check your inbox (and spam folder).');
        }
        
        return $this->redirectToRoute('app_login');
    }

    #[Route('/check-verification-status', name: 'app_check_verification')]
    public function checkVerificationStatus(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['verified' => false, 'message' => 'Not logged in'], 401);
        }
        
        return $this->json([
            'verified' => $user->isVerified(),
            'email' => $user->getEmail(),
            'verified_at' => $user->getVerifiedAt()?->format('Y-m-d H:i:s')
        ]);
    }
}
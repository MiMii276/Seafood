<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->urlGenerator->generate('app_verify_email', [
            'token' => $user->getVerificationToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('noreply@seafoodie.com')
            ->to($user->getEmail())
            ->subject('Verify Your Email - Seafoodie')
            ->html($this->getVerificationEmailTemplate($user->getName(), $verificationUrl));

        $this->mailer->send($email);
    }

    public function verifyEmail(string $token): ?User
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerifiedAt(new \DateTimeImmutable());
        $user->setVerificationToken(null);
        
        $this->entityManager->flush();

        return $user;
    }

    private function getVerificationEmailTemplate(string $name, string $verificationUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f7fc; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #0a2b2e, #1f7a8c); padding: 30px; text-align: center; }
                .header h1 { color: white; margin: 0; }
                .content { padding: 30px; }
                .button { display: inline-block; background: linear-gradient(135deg, #1f7a8c, #0a5c6b); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🐟 Seafoodie</h1>
                </div>
                <div class='content'>
                    <h2>Welcome to Seafoodie, {$name}!</h2>
                    <p>Please verify your email address to complete your registration.</p>
                    <a href='{$verificationUrl}' class='button'>Verify Email Address</a>
                    <p>Or copy this link: <br><small>{$verificationUrl}</small></p>
                    <p>This link expires in 24 hours.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Seafoodie. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
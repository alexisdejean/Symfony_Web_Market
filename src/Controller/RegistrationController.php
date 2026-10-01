<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\FileRateLimiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(bool:EMAIL_VERIFICATION_ENABLED)%')]
        private bool $emailVerificationEnabled,
        #[Autowire('%env(MAILER_DSN)%')]
        private string $mailerDsn,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailVerifier $emailVerifier,
        FileRateLimiter $rateLimiter,
    ): Response {
        if ($request->isMethod('POST') && !$rateLimiter->consume('registration', (string) $request->getClientIp(), 5, 3600)) {
            $this->addFlash('error', 'Trop de tentatives d’inscription. Réessayez plus tard.');

            return $this->redirectToRoute('app_register');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->emailVerificationEnabled && str_starts_with(strtolower($this->mailerDsn), 'null://')) {
                $this->addFlash('error', 'L’inscription nécessite un service d’envoi d’emails configuré.');

                return $this->redirectToRoute('app_register');
            }

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setIsVerified(!$this->emailVerificationEnabled);

            $entityManager->persist($user);
            $entityManager->flush();

            if ($this->emailVerificationEnabled) {
                try {
                    $emailVerifier->sendEmailConfirmation(
                        'app_verify_email',
                        $user,
                        (new TemplatedEmail())
                            ->from(new Address('contact@glassngo.com', 'GlassNGo'))
                            ->to((string) $user->getEmail())
                            ->subject('Confirmez votre adresse email')
                            ->htmlTemplate('registration/confirmation_email.html.twig')
                    );
                } catch (TransportExceptionInterface) {
                    $entityManager->remove($user);
                    $entityManager->flush();
                    $this->addFlash('error', 'Le message de confirmation n’a pas pu être envoyé. Réessayez plus tard.');

                    return $this->redirectToRoute('app_register');
                }

                $this->addFlash('success', 'Un lien de confirmation a été envoyé à votre adresse email.');
            } else {
                $this->addFlash('success', 'Votre compte a été créé. Vous pouvez vous connecter.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository, EmailVerifier $emailVerifier): Response
    {
        $userId = $request->query->get('id');
        if (!is_string($userId) || !ctype_digit($userId)) {
            throw $this->createNotFoundException('Lien de confirmation invalide.');
        }

        $user = $userRepository->find((int) $userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        try {
            $emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface) {
            $this->addFlash('verify_email_error', 'Le lien de confirmation est invalide ou expiré.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Votre adresse email est confirmée. Vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}

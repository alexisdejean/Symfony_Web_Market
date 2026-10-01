<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private FileRateLimiter $rateLimiter,
        #[Autowire('%env(bool:EMAIL_VERIFICATION_ENABLED)%')]
        private bool $emailVerificationEnabled,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $identifiant = trim((string) $request->getPayload()->getString('identifiant', ''));
        $ip = (string) ($request->getClientIp() ?? 'unknown');
        if (!$this->rateLimiter->consume('login-ip', $ip, 30, 60)
            || !$this->rateLimiter->consume('login-credentials', $ip.':'.strtolower($identifiant), 5, 300)) {
            throw new CustomUserMessageAuthenticationException('Trop de tentatives de connexion. Réessayez plus tard.');
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $identifiant);

        return new Passport(
            new UserBadge($identifiant, function (string $userIdentifier) {
                $user = $this->userRepository->findOneBy(['identifiant' => $userIdentifier]);

                if (!$user || $user->isStatus() || ($this->emailVerificationEnabled && !$user->isVerified())) {
                    throw new CustomUserMessageAuthenticationException('Identifiant ou mot de passe incorrect.');
                }

                return $user;
            }),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('home_page')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

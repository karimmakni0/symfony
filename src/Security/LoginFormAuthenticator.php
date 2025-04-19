<?php

namespace App\Security;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $entityManager;
    private $urlGenerator;
    private $usersRepository;
    private $session;
    private $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager, 
        UrlGeneratorInterface $urlGenerator, 
        UsersRepository $usersRepository,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->usersRepository = $usersRepository;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');
        $rememberMe = $request->request->get('_remember_me', false);

        // If this is a normal login request and OTP verification has been completed already
        if ($this->session->has('2fa_verified') && $this->session->get('2fa_verified') === true) {
            $userId = $this->session->get('2fa_user_id');
            $user = $this->usersRepository->find($userId);
            
            if (!$user) {
                throw new CustomUserMessageAuthenticationException('User not found.');
            }
            
            // Create a self-validating passport since we've already verified both factors
            return new SelfValidatingPassport(
                new UserBadge($user->getEmail(), function ($userIdentifier) use ($user) {
                    return $user; // Return the user directly since we already have it
                })
            );
        }
        
        // Store email in session for future use
        $request->getSession()->set(Security::LAST_USERNAME, $email);

        // Find the user
        $user = $this->usersRepository->findOneBy(['email' => $email]);
        
        // Check if user exists
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }
        
        // Check if user is enabled
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException('Your account is not activated. Please complete the registration process.');
        }
        
        // Create a passport with credentials to validate password
        $passport = new Passport(
            new UserBadge($email, function ($userIdentifier) {
                return $this->usersRepository->findOneBy(['email' => $userIdentifier]);
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
        
        if ($rememberMe) {
            $passport->addBadge(new RememberMeBadge());
        }
        
        // Store login information in session
        $this->session->set('pending_login', [
            'email' => $email,
            'user_id' => $user->getId(),
            'remember_me' => $rememberMe
        ]);
        
        // Return the passport for password validation
        // But we'll interrupt the authentication in onAuthenticationSuccess
        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // For regular logins, redirect to 2FA page instead of completing login
        if (!$this->session->has('2fa_verified')) {
            // Get the authenticated user
            $user = $token->getUser();
            
            // Invalidate the current token so the user isn't authenticated yet
            $this->tokenStorage->setToken(null);
            
            // Store the user ID for 2FA verification
            $this->session->set('totp_user_id', $user->getId());
            
            // Redirect to OTP verification
            return new RedirectResponse($this->urlGenerator->generate('app_totp_verify_page'));
        }
        
        // If 2FA is verified, we're completing a full login
        // Get the user and clear the 2FA verified flags
        $user = $token->getUser();
        $this->session->remove('2fa_verified');
        $this->session->remove('2fa_user_id');
        
        // Now that authentication is complete (after BOTH factors), redirect to appropriate page
        $role = $user->getRole();
        
        if ($role === 'admin') {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        } elseif ($role === 'Publicitaire') {
            return new RedirectResponse($this->urlGenerator->generate('app_publicator'));
        } else {
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

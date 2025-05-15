<?php

namespace App\Security;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserAuthAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->attributes->get('_route') === 'app_login';
    }

public function authenticate(Request $request): Passport
{
    $username = $request->request->get('email', '');
    $password = $request->request->get('password', '');
    $csrfToken = $request->request->get('_csrf_token');

    return new Passport(
        new UserBadge($username),
        new PasswordCredentials($password),
        [
            new CsrfTokenBadge('authenticate', $csrfToken),
        ]
    );
}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

    if ($targetPath) {
        return new RedirectResponse($targetPath);
    }
        // Get the authenticated user
        $user = $token->getUser();
        
        // Redirect based on user roles
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        } elseif (in_array('ROLE_UNIVERSITE', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('universite_index'));
        } elseif (in_array('ROLE_ETABLISSEMENT', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('etablissement_dashboard'));
        }

        // Default redirect for other users
        return new RedirectResponse($this->urlGenerator->generate('user_profile'));
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?Response
    {
        // Optionally, redirect back to the login page with an error
        $request->getSession()->set('error', $exception->getMessage());
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}






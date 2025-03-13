<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JWTAuthenticator extends AbstractAuthenticator
{
    private JWTManager $jwtManager;

    private UserProviderInterface $userProvider;

    public function __construct(JWTManager $jwtManager, UserProviderInterface $userProvider)
    {
        $this->jwtManager = $jwtManager;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): ?bool
    {
        // Vérifie si l'en-tête Authorization est présent dans la requête
        return $request->headers->has('Authorization');
    }

    public function getCredentials(Request $request)
    {
        // Extraire le token depuis l'en-tête Authorization
        $authHeader = $request->headers->get('Authorization');
        return str_replace('Bearer ', '', $authHeader); // Retirer le "Bearer " du token
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
        return new SelfValidatingPassport(new UserBadge($credentials, function($credentials) {
            return $this->getUser($credentials, $this->userProvider);
        }));
    }


    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // Décoder le token et récupérer l'utilisateur
        try {
            return $this->jwtManager->parse($credentials);
        } catch (\Exception $e) {
            return null; // Si le token est invalide, on ne retourne pas d'utilisateur
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // La vérification est déjà effectuée dans la méthode getUser, donc on retourne true
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Si l'authentification échoue, on retourne une réponse 401
        return new Response('Authentication failed: ' . $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token, string $firewallName): ?Response
    {
        // Si l'authentification réussit, on ne fait rien ici, l'accès est autorisé
        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        // Lorsque l'authentification est requise, mais absente, renvoyer une erreur 401
        return new Response('Authentication required', Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        // Pas de gestion de "Remember Me"
        return false;
    }
}

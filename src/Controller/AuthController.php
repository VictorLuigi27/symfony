<?php

// src/Controller/AuthController.php

// src/Controller/AuthController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends AbstractController
{
    private $passwordEncoder;
    private $userRepository;
    private $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordEncoder, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifier les données
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email et mot de passe sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username'] ?? 'default'); // Ajout de la valeur par défaut ici
        
        $hashedPassword = $this->passwordEncoder->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Générer un JWT
        $key = 'secret'; // Clé secrète
        $payload = [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'exp' => time() + 3600 // Expire dans 1 heure
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        // Retourner la réponse JSON
        return new JsonResponse(['message' => 'Utilisateur créé avec succès.', 'token' => $jwt], Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email et mot de passe sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordEncoder->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Identifiants incorrects'], Response::HTTP_UNAUTHORIZED);
        }

        // Créer un token JWT
        $key = 'secret'; // Clé secrète
        $payload = [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'exp' => time() + 3600 // Expire dans 1 heure
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        return new JsonResponse([
            'message' => 'Connexion réussie',
            'token' => $jwt
        ], Response::HTTP_OK);
    }
}


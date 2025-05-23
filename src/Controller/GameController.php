<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/game')]
final class GameController extends AbstractController
{
    // Récupérer tous les jeux
    #[Route(name: 'app_game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findAll();

        // Transformer les jeux en tableau pour les renvoyer en JSON, y compris les catégories
        $gamesData = array_map(function($game) {
            $categories = $game->getCategories(); // Assumer que la méthode getCategories() existe
            $categoriesData = array_map(function($category) {
                return $category->getName(); // Retourne le nom de la catégorie
            }, $categories->toArray()); // Assurer que getCategories retourne une collection

            return [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'picture' => $game->getPicture(),
                'categories' => $categoriesData, // Ajouter les catégories
            ];
        }, $games);

        return new JsonResponse($gamesData);  // Retourner les données au format JSON
    }

    // Créer un nouveau jeu sans catégories
    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires
        if (empty($data['title']) || empty($data['description']) || empty($data['picture'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        // Vérification de l'URL de l'image
        $imageUrl = $data['picture'];
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['error' => 'Invalid image URL'], 400);
        }

        // Création du jeu
        $game = new Game();
        $game->setTitle($data['title'])
            ->setDescription($data['description'])
            ->setPicture($imageUrl);

        // Sauvegarde en base de données
        $entityManager->persist($game);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Game created successfully',
            'game' => [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'picture' => $game->getPicture()
            ]
        ], 201);
    }

    // Afficher un jeu spécifique
    #[Route('/{id}', name: 'app_game_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $game = $em->getRepository(Game::class)->find($id);

        if (!$game) {
            return new JsonResponse(['error' => 'Jeu non trouvé'], 404);
        }

        $gameData = [
            'id' => $game->getId(),
            'title' => $game->getTitle(),
            'description' => $game->getDescription(),
            'picture' => $game->getPicture(),
            'categories' => array_map(function($category) {
                return $category->getName();
            }, $game->getCategories()->toArray()), // Ajouter les catégories associées
        ];

        return new JsonResponse($gameData);
    }

    // Modifier un jeu existant
    #[Route('/{id}/edit', name: 'app_game_edit', methods: ['PUT'])]
    public function edit(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $game->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $game->setDescription($data['description']);
        }
        if (isset($data['picture'])) {
            $game->setPicture($data['picture']);  // Modifier l'image
        }

        if (isset($data['categories'])) {
            $categories = $entityManager->getRepository(Category::class)->findBy(['id' => $data['categories']]);
            foreach ($categories as $category) {
                $game->addCategory($category);
            }
        }

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Game updated successfully',
            'game' => [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'picture' => $game->getPicture(),
                'categories' => array_map(function($category) {
                    return $category->getName();
                }, $game->getCategories()->toArray()), // Retourner les catégories mises à jour
            ]
        ]);
    }

    // Supprimer un jeu
    #[Route('/{id}', name: 'app_game_delete', methods: ['DELETE'])]
    public function delete(Game $game, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($game);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Game deleted successfully']);
    }
}
<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/game')]
final class GameController extends AbstractController
{
    // Récupérer tous les jeux
    #[Route(name: 'app_game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findAll();

        // Transformer les jeux en tableau pour les renvoyer en JSON
        $gamesData = array_map(function($game) {
            return [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'picture' => $game->getPicture(),  // Ajouter l'image
            ];
        }, $games);

        return new JsonResponse($gamesData);  // Retourner les données au format JSON
    }

    // Créer un nouveau jeu
    #[Route('/new', name: 'app_game_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);

        // Vérifier que toutes les informations nécessaires sont présentes
        if (!isset($data['title'], $data['description'], $data['picture'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        $game = new Game();
        $game->setTitle($data['title']);
        $game->setDescription($data['description']);
        $game->setPicture($data['picture']);  

        $entityManager->persist($game);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Game created successfully',
            'game' => [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'picture' => $game->getPicture(),  
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

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Game updated successfully',
            'game' => [
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'description' => $game->getDescription(),
                'picture' => $game->getPicture(),  // Ajouter l'image dans la réponse
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
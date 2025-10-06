<?php

namespace App\Contracts;

interface PlantServiceInterface
{
    /**
     * Récupère les données des plantes depuis l'API externe
     */
    public function fetchPlantsFromApi(): array;

    /**
     * Met à jour la base de données avec les nouvelles données
     */
    public function updatePlantsDatabase(array $plants): array;

    /**
     * Synchronise toutes les plantes
     */
    public function syncAllPlants(): array;
}
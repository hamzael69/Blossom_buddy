<?php

namespace App\Services;

use App\Contracts\PlantServiceInterface;
use App\Models\Plant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlantService implements PlantServiceInterface
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.plant_api.url', 'https://perenual.com/api/v2');
        $this->apiKey = config('services.plant_api.key', 'sk-zDEN68e38c96875cd12717');
    }

    /**
     * Récupère les données des plantes depuis l'API externe
     */
    public function fetchPlantsFromApi(): array
    {
        try {
            $response = Http::get($this->apiUrl . '/species-list', [
                'key' => $this->apiKey,
                'page' => 1,
                'per_page' => 30 // Limite pour les tests
            ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            Log::error('Erreur API plantes: ' . $response->body());
            return [];

        } catch (\Exception $e) {
            Log::error('Exception lors de la récupération des plantes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour la base de données avec les nouvelles données
     */
    public function updatePlantsDatabase(array $plants): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'errors' => 0
        ];

        foreach ($plants as $plantData) {
            try {
                $filteredData = $this->filterPlantData($plantData);
                
                if ($filteredData) {
                    $plant = Plant::updateOrCreate(
                        ['common_name' => $filteredData['common_name']],
                        $filteredData
                    );

                    if ($plant->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Erreur lors de la sauvegarde de la plante: ' . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Synchronise toutes les plantes
     */
    public function syncAllPlants(): array
    {
        $plants = $this->fetchPlantsFromApi();
        
        if (empty($plants)) {
            return ['error' => 'Aucune donnée récupérée depuis l\'API'];
        }

        return $this->updatePlantsDatabase($plants);
    }

    /**
     * Filtre et formate les données de plante
     */
    private function filterPlantData(array $plantData): ?array
    {
        // Vérifie que les données essentielles sont présentes
        if (!isset($plantData['common_name']) || empty($plantData['common_name'])) {
            return null;
        }

        // Extrait les informations d'arrosage ou utilise des valeurs par défaut
        $watering = $this->extractWateringInfo($plantData);

        return [
            'common_name' => $plantData['common_name'],
            'watering_general_benchmark' => $watering
        ];
    }

    /**
     * Extrait les informations d'arrosage depuis les données API
     */
    private function extractWateringInfo(array $plantData): array
    {
        // Adapte selon la structure de ton API
        if (isset($plantData['watering'])) {
            return [
                'value' => $plantData['watering'],
                'unit' => 'days'
            ];
        }

        // Valeurs par défaut si pas d'info d'arrosage
        return [
            'value' => '7-10',
            'unit' => 'days'
        ];
    }
}
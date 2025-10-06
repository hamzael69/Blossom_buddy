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
        $allPlants = [];
        $currentPage = 1;
        $perPage = 30; // Maximum autorisé par l'API
        
        try {
            do {
                Log::info("Récupération de la page {$currentPage}...");
                
                $response = Http::timeout(60)->get($this->apiUrl . '/species-list', [
                    'key' => $this->apiKey,
                    'page' => $currentPage,
                    'per_page' => $perPage
                ]);

                if (!$response->successful()) {
                    Log::error("Erreur API page {$currentPage}: " . $response->body());
                    break;
                }

                $data = $response->json();
                $plants = $data['data'] ?? [];
                
                if (empty($plants)) {
                    Log::info("Aucune plante trouvée sur la page {$currentPage}. Arrêt.");
                    break;
                }

                // Ajoute les plantes de cette page au tableau global
                $allPlants = array_merge($allPlants, $plants);
                
                Log::info("Page {$currentPage}: " . count($plants) . " plantes récupérées. Total: " . count($allPlants));

                // Vérification s'il y a une page suivante
                $totalPages = $data['last_page'] ?? null;
                $hasNextPage = isset($data['links']['next']) || ($totalPages && $currentPage < $totalPages);
                
                if (!$hasNextPage) {
                    Log::info("Dernière page atteinte. Arrêt.");
                    break;
                }

                $currentPage++;
                
                // Petite pause pour éviter de surcharger l'API
                sleep(1);
                
                // Limite de sécurité pour éviter une boucle infinie
                if ($currentPage > 100) {
                    Log::warning("Limite de 100 pages atteinte. Arrêt pour sécurité.");
                    break;
                }
                
            } while (true);

        } catch (\Exception $e) {
            Log::error('Exception lors de la récupération des plantes: ' . $e->getMessage());
        }

        Log::info("Total final: " . count($allPlants) . " plantes récupérées");
        return $allPlants;
    }

    /**
     * Met à jour la base de données avec les nouvelles données
     */
    public function updatePlantsDatabase(array $plants): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'total_processed' => count($plants)
        ];

        Log::info("Début de la mise à jour de la base de données avec " . count($plants) . " plantes");

        foreach ($plants as $index => $plantData) {
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
                
                // Log de progression tous les 100 éléments
                if (($index + 1) % 100 === 0) {
                    Log::info("Progression: " . ($index + 1) . "/" . count($plants) . " plantes traitées");
                }
                
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Erreur lors de la sauvegarde de la plante: ' . $e->getMessage());
            }
        }

        Log::info("Mise à jour terminée: {$stats['created']} créées, {$stats['updated']} mises à jour, {$stats['errors']} erreurs");
        return $stats;
    }

    /**
     * Synchronise toutes les plantes
     */
    public function syncAllPlants(): array
    {
        Log::info("Début de la synchronisation complète des plantes");
        
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
        // Adapte selon la structure de l'API Perenual
        if (isset($plantData['watering'])) {
            return [
                'value' => $plantData['watering'],
                'unit' => 'frequency'
            ];
        }

        // Utilise d'autres champs si disponibles
        if (isset($plantData['care_level'])) {
            $careLevel = strtolower($plantData['care_level']);
            switch ($careLevel) {
                case 'low':
                    return ['value' => '10-14', 'unit' => 'days'];
                case 'medium':
                    return ['value' => '5-7', 'unit' => 'days'];
                case 'high':
                    return ['value' => '2-3', 'unit' => 'days'];
            }
        }

        // Valeurs par défaut si pas d'info d'arrosage
        return [
            'value' => '7-10',
            'unit' => 'days'
        ];
    }
}
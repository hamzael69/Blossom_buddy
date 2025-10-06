<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class UserPlantController extends Controller
{
     /**
     * @OA\Post(
     *     path="/api/user/plant",
     *     tags={"User Plants"},
     *     summary="Ajouter une plante à un utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"common_name","city"},
     *             @OA\Property(property="common_name", type="string", example="Ficus"),
     *             @OA\Property(property="city", type="string", example="Paris")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plante ajoutée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Plante ajoutée à l'utilisateur")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Plante non trouvée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'common_name' => 'required|string',
            'city' => 'required|string',
        ]);

        $plant = Plant::where('common_name', $request->common_name)->first();

        if (!$plant) {
            return response()->json(['error' => 'Plante non trouvée'], 404);
        }

        $user = $request->user();

        // Ajout dans la table pivot avec la ville
        $user->plants()->attach($plant->id, ['city' => $request->city]);

        return response()->json(['message' => 'Plante ajoutée à l’utilisateur']);
    }

     /**
     * @OA\Get(
     *     path="/api/user/plants",
     *     tags={"User Plants"},
     *     summary="Récupérer toutes les plantes de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des plantes de l'utilisateur",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="common_name", type="string", example="Ficus"),
     *                 @OA\Property(property="watering_general_benchmark", type="object"),
     *                 @OA\Property(property="pivot", type="object",
     *                     @OA\Property(property="city", type="string", example="Paris")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $user = $request->user();
        $plants = $user->plants()->withPivot('city')->get();

        return response()->json($plants);
    }

     /**
     * @OA\Delete(
     *     path="/api/user/plant/{id}",
     *     tags={"User Plants"},
     *     summary="Supprimer une plante de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID de la relation plante-utilisateur"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plante supprimée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Plante retirée de la liste de l'utilisateur")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Relation plante-utilisateur non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Relation plante-utilisateur non trouvée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // Vérifie si la plante est bien associée à l'utilisateur
        $exists = $user->plants()->wherePivot('id', $id)->exists();

        if (!$exists) {
            return response()->json(['error' => 'Relation plante-utilisateur non trouvée'], 404);
        }

        // Supprime la relation dans la table pivot
        $user->plants()->wherePivot('id', $id)->detach();

        return response()->json(['message' => 'Plante retirée de la liste de l’utilisateur']);
    }
}

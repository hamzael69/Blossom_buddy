<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
      /**
     * @OA\Get(
     *     path="/api/plant",
     *     tags={"Plants"},
     *     summary="Récupérer toutes les plantes",
     *     @OA\Response(
     *         response=200,
     *         description="Liste de toutes les plantes",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="common_name", type="string", example="Ficus"),
     *                 @OA\Property(property="watering_general_benchmark", type="object",
     *                     @OA\Property(property="value", type="string", example="5-7"),
     *                     @OA\Property(property="unit", type="string", example="days")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return Plant::all();
    }


    /**
     * @OA\Post(
     *     path="/api/plant",
     *     tags={"Plants"},
     *     summary="Ajouter une nouvelle plante",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"common_name","watering_general_benchmark"},
     *             @OA\Property(property="common_name", type="string", example="Ficus"),
     *             @OA\Property(property="watering_general_benchmark", type="object",
     *                 @OA\Property(property="value", type="string", example="5-7"),
     *                 @OA\Property(property="unit", type="string", example="days")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plante créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="common_name", type="string", example="Ficus"),
     *             @OA\Property(property="watering_general_benchmark", type="object"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'common_name' => 'required|string',
            'watering_general_benchmark' => 'required|array',

        ]);

        $plant = \App\Models\Plant::create([
            'common_name' => $validated['common_name'],
            'watering_general_benchmark' => $validated['watering_general_benchmark'],
        ]);

        return response()->json($plant, 201);
    }

      /**
     * @OA\Get(
     *     path="/api/plant/{name}",
     *     tags={"Plants"},
     *     summary="Récupérer une plante par son nom",
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Nom commun de la plante",
     *         example="Ficus"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informations de la plante",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="common_name", type="string", example="Ficus"),
     *             @OA\Property(property="watering_general_benchmark", type="object"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Plante non trouvée")
     *         )
     *     )
     * )
     */

    public function show($name)
    {
        $plant = \App\Models\Plant::where('common_name', $name)->first();

        if (!$plant) {
            return response()->json([
                'error' => 'Plante non trouvée'
            ], 404);
        }

        return response()->json($plant);
    }

      /**
     * @OA\Delete(
     *     path="/api/plant/{id}",
     *     tags={"Plants"},
     *     summary="Supprimer une plante",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID de la plante à supprimer",
     *         example=1
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plante supprimée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Plante supprimée avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Plante non trouvée")
     *         )
     *     )
     * )
     */


    public function destroy($id)
    {
        $plant = Plant::find($id);

        if (!$plant) {
            return response()->json([
                'error' => 'Plante non trouvée'
            ], 404);
        }

        $plant->delete();

        return response()->json([
            'message' => 'Plante supprimée avec succès'
        ]);
    }
}

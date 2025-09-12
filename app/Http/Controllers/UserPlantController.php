<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class UserPlantController extends Controller
{
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


    public function index(Request $request)
    {
        $user = $request->user();
        $plants = $user->plants()->withPivot('city')->get();

        return response()->json($plants);
    }

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

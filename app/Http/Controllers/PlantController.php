<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index()
    {
        return Plant::all();
    }


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

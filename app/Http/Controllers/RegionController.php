<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $regions = Region::all();
        return response()->json($regions, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'default_delivery_cost' => 'required|numeric|min:0',
        ]);

        $region = Region::create($request->only(['name', 'default_delivery_cost']));

        return response()->json($region, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Region $region) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        $region = Region::findOrFail($id);
        if (!$region) {
            return response()->json(['message' => 'Region not found'], 404);
        }

        $valiator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'default_delivery_cost' => 'sometimes|required|numeric|min:0',
        ]);

        if ($valiator->fails()) {
            return response()->json(['errors' => $valiator->errors()], 422);
        }

        $region->update($request->only(['name', 'default_delivery_cost']));
        return response()->json($region, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $region = Region::findOrFail($id);
        if (!$region) {
            return response()->json(['message' => 'Region not found'], 404);
        }

        $region->delete();
        return response()->json(['message' => 'Region deleted successfully'], 200);
    }
}

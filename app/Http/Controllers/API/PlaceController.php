<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PlaceController extends Controller
{
    public function index()
    {
        try {
            $places = Place::orderBy('created_at', 'desc')->get();
            return response()->json([
                'success' => true,
                'data' => $places
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching places: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch places',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'       => 'required|string|max:255',
                'location'   => 'required|string|max:255',
                'category'   => 'required|integer|min:1|max:6',
                'map_url'    => 'nullable|url',
                'open_hours' => 'nullable|string',
                'photo'      => 'nullable|url|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $place = Place::create([
                'name'       => $request->name,
                'location'   => $request->location,
                'category'   => $request->category,
                'map_url'    => $request->map_url,
                'open_hours' => $request->open_hours,
                'photo'      => $request->photo,
            ]);

            Log::info('Place created successfully: ' . $place->name);

            return response()->json([
                'success' => true,
                'message' => 'Place created successfully',
                'data'    => $place,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating place: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create place',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $place = Place::find($id);
            if (!$place) {
                return response()->json([
                    'success' => false,
                    'message' => 'Place not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $place
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching place: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch place',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $place = Place::find($id);
            if (!$place) {
                return response()->json([
                    'success' => false,
                    'message' => 'Place not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name'       => 'sometimes|required|string|max:255',
                'location'   => 'sometimes|required|string|max:255',
                'category'   => 'sometimes|required|integer|min:1|max:6',
                'map_url'    => 'sometimes|required|url',
                'open_hours' => 'sometimes|required|string',
                'photo'      => 'sometimes|required|url|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $place->fill($request->only(['name', 'location', 'category', 'map_url', 'open_hours', 'photo']));
            $place->save();

            Log::info('Place updated successfully: ' . $place->name);

            return response()->json([
                'success' => true,
                'message' => 'Place updated successfully',
                'data' => $place
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating place: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update place',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Log::info('Attempting to delete place with ID: ' . $id);
            
            $place = Place::find($id);
            
            if (!$place) {
                Log::warning('Place not found with ID: ' . $id);
                return response()->json([
                    'success' => false,
                    'message' => 'Place not found'
                ], 404);
            }
            
            Log::info('Deleting place:', [
                'id' => $place->id,
                'name' => $place->name,
                'photo' => $place->photo
            ]);
            
            $place->delete();
            
            Log::info('Place deleted successfully. ID: ' . $id);
            
            return response()->json([
                'success' => true,
                'message' => 'Place deleted successfully',
                'data' => [
                    'id' => $id,
                    'name' => $place->name
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting place: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete place',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
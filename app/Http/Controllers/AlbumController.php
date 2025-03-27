<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Song;
use Illuminate\Support\Facades\Log; // Import Log

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlbumController extends Controller
{
    // 🟢 CREATE ALBUM
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Store image
        $imagePath = $request->file('image')->store('albums', 'public');


        $userId = auth()->id();
        Log::info("Authenticated user ID: " . ($userId ?? 'null')); // ✅ Log user ID
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized. Please log in.'], 401);
        }

        $album = Album::create([
            'name' => $request->name,
            'desc' => $request->desc,
            'image' => $imagePath,
            'date' => $request->date,
            'user_id' => $userId, // Associate album with the logged-in user
        ]);
        Log::info("Album created with user ID: " . $album->user_id); // ✅ Log stored user ID

        return response()->json([
            'message' => 'Album created successfully!',
            'album' => [
                'id' => $album->id,
                'name' => $album->name,
                'desc' => $album->desc,
                'image' => url('storage/' . $album->image), // Full URL
                'date' => $album->date,
                'user_id' => $album->user_id, // ✅ Return user_id for verification
            ]
        ], 201);

    }

    // 🔵 GET ALL ALBUMS
    public function index()
    {
        $userId = auth()->id(); // Get the logged-in user's ID
        $albums = Album::where('user_id', $userId)->get()->map(function ($album) {
            $album->image = url('storage/' . $album->image); // Convert to full URL
            return $album;
        });
        return response()->json($albums, 200);
    }

    // 🔵 GET SINGLE ALBUM
    public function show($id)
    {
        try {
            $album = Album::where('id', $id)->where('user_id', auth()->id())->first();

            if (!$album) {
                return response()->json(['error' => 'Album not found or access denied!'], 404);
            }

            $album->image = url('storage/' . $album->image); // Convert to full URL
            return response()->json($album, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Album not found!'], 404);
        }
    }

    // 🟡 UPDATE ALBUM
    public function update(Request $request, $id)
    {
        try {
            $album = Album::where('id', $id)->where('user_id', auth()->id())->first();

            if (!$album) {
                return response()->json(['error' => 'Album not found or access denied!'], 404);
            }


            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'desc' => 'sometimes|string',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                'date' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->has('name')) {
                $album->name = $request->name;
            }
            if ($request->has('desc')) {
                $album->desc = $request->desc;
            }
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('albums', 'public');
                $album->image = $imagePath;
            }
            if ($request->has('date')) {
                $album->date = $request->date;
            }

            $album->save();
            return response()->json(['message' => 'Album updated successfully!', 'album' => $album], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Album not found!'], 404);
        }
    }

    // 🔴 DELETE ALBUM
    public function destroy($id)
    {
        \Log::info("Attempting to delete album with ID: " . $id);
        try {
            $album = Album::where('id', $id)->where('user_id', auth()->id())->first();
            \Log::info("Album found: " . json_encode($album));

            if (!$album) {
                return response()->json(['error' => 'Album not found or access denied!'], 404);
            }

            Song::where('album_id', $id)->update(['album_id' => null]);
            $album->delete();
            \Log::info("Album deleted successfully");
            return response()->json(['message' => 'Album deleted successfully!'], 200);
        } catch (\Exception $e) {
            \Log::error("Album not found or error deleting: " . $e->getMessage());
            return response()->json(['error' => 'Album not found!'], 404);
        }
    }

    // 🔵 GET LOGGED-IN USER'S ALBUMS
    public function getUserAlbums()
    {
        $userId = auth()->id(); // Get logged-in user's ID

        $albums = Album::where('user_id', $userId)->get()->map(function ($album) {
            $album->image = url('storage/' . $album->image); // Convert image path to full URL
            return $album;
        });

        return response()->json($albums, 200);
    }

}

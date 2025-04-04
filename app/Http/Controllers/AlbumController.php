<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Cloudinary\Cloudinary;

class AlbumController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => config('cloudinary.cloud'),
            'url' => config('cloudinary.url'),
        ]);
    }

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

        $userId = auth()->id();
        Log::info("Authenticated user ID: " . ($userId ?? 'null'));
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized. Please log in.'], 401);
        }

        // Upload to Cloudinary
        $uploaded = $this->cloudinary->uploadApi()->upload($request->file('image')->getRealPath());
        $imageUrl = $uploaded['secure_url'];

        $album = Album::create([
            'name' => $request->name,
            'desc' => $request->desc,
            'image' => $imageUrl,
            'date' => $request->date,
            'user_id' => $userId,
        ]);

        return response()->json([
            'message' => 'Album created successfully!',
            'album' => $album,
        ], 201);
    }

    // 🔵 GET ALL ALBUMS
    public function index()
    {
        $userId = auth()->id();
        $albums = Album::where('user_id', $userId)->get();
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

            if ($request->has('name'))
                $album->name = $request->name;
            if ($request->has('desc'))
                $album->desc = $request->desc;
            if ($request->has('date'))
                $album->date = $request->date;

            if ($request->hasFile('image')) {
                $uploaded = $this->cloudinary->uploadApi()->upload($request->file('image')->getRealPath());
                $album->image = $uploaded['secure_url'];
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
        Log::info("Attempting to delete album with ID: " . $id);

        try {
            $album = Album::where('id', $id)->where('user_id', auth()->id())->first();
            Log::info("Album found: " . json_encode($album));

            if (!$album) {
                return response()->json(['error' => 'Album not found or access denied!'], 404);
            }

            Song::where('album_id', $id)->update(['album_id' => null]);
            $album->delete();

            Log::info("Album deleted successfully");
            return response()->json(['message' => 'Album deleted successfully!'], 200);
        } catch (\Exception $e) {
            Log::error("Album not found or error deleting: " . $e->getMessage());
            return response()->json(['error' => 'Album not found!'], 404);
        }
    }

    // 🔵 GET LOGGED-IN USER'S ALBUMS
    public function getUserAlbums()
    {
        $userId = auth()->id();
        $albums = Album::where('user_id', $userId)->get();

        return response()->json($albums, 200);
    }
}

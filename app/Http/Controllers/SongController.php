<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Cloudinary\Cloudinary;

class SongController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => config('cloudinary.cloud'),
            'url' => config('cloudinary.url'),
        ]);
    }

    // ✅ Store a new song
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'albumId' => 'required|exists:albums,id',
            'genre' => 'required|string',
            'duration' => 'required|string',
            'date' => 'nullable|string',
        ]);

        $album = Album::where('id', $request->albumId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$album) {
            return response()->json(['error' => 'Album not found or does not belong to you!'], 403);
        }

        // 🔼 Upload to Cloudinary
        $uploaded = $this->cloudinary->uploadApi()->upload($request->file('image')->getRealPath());
        $imageUrl = $uploaded['secure_url'];

        $song = Song::create([
            'name' => $request->name,
            'desc' => $request->desc,
            'image' => $imageUrl,
            'album_id' => $request->albumId,
            'genre' => $request->genre,
            'duration' => $request->duration,
            'date' => $request->date,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Song uploaded successfully!',
            'song' => $song
        ], 201);
    }

    // ✅ Get all songs
    public function index()
    {
        $songs = Song::where('user_id', auth()->id())->get();
        return response()->json($songs);
    }

    // ✅ Get a single song
    public function show($id)
    {
        $song = Song::where('id', $id)->where('user_id', auth()->id())->first();

        if (!$song) {
            return response()->json(['message' => 'Song not found'], 404);
        }

        return response()->json($song);
    }

    // 🛠️ Update song
    public function update(Request $request, $id)
    {
        try {
            $song = Song::where('id', $id)->where('user_id', auth()->id())->first();

            if (!$song) {
                return response()->json(['error' => 'Song not found or unauthorized!'], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'desc' => 'sometimes|string',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                'albumId' => 'sometimes|integer|exists:albums,id',
                'genre' => 'sometimes|string',
                'duration' => 'sometimes|string',
                'date' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->has('name'))
                $song->name = $request->name;
            if ($request->has('desc'))
                $song->desc = $request->desc;
            if ($request->has('genre'))
                $song->genre = $request->genre;
            if ($request->has('duration'))
                $song->duration = $request->duration;
            if ($request->has('date'))
                $song->date = $request->date;

            if ($request->has('albumId')) {
                $album = Album::where('id', $request->albumId)
                    ->where('user_id', auth()->id())
                    ->first();

                if (!$album) {
                    return response()->json(['error' => 'Album not found or unauthorized!'], 403);
                }

                $song->album_id = $request->albumId;
            }

            if ($request->hasFile('image')) {
                $uploaded = $this->cloudinary->uploadApi()->upload($request->file('image')->getRealPath());
                $song->image = $uploaded['secure_url'];
            }

            $song->save();

            return response()->json(['message' => 'Song updated successfully!', 'song' => $song], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Song not found or update failed!'], 404);
        }
    }

    // ✅ Delete a song
    public function destroy($id)
    {
        $song = Song::where('id', $id)->where('user_id', auth()->id())->first();

        if (!$song) {
            return response()->json(['message' => 'Song not found'], 404);
        }

        $song->delete();

        return response()->json(['message' => 'Song deleted successfully!']);
    }

    // 🔵 Get songs by album
    public function getSongsByAlbum($albumId)
    {
        $album = Album::where('id', $albumId)->where('user_id', auth()->id())->first();

        if (!$album) {
            return response()->json(['message' => 'Album not found'], 404);
        }

        $songs = Song::where('album_id', $albumId)->where('user_id', auth()->id())->get();
        return response()->json($songs);
    }

    // 🔵 Get all songs for logged-in user
    public function getUserSongs()
    {
        $userId = auth()->id();
        $songs = Song::where('user_id', $userId)->get();

        return response()->json($songs, 200);
    }
}

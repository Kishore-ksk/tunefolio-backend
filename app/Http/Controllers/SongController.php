<?php

namespace App\Http\Controllers;
use App\Models\Album;

use Illuminate\Http\Request;
use App\Models\Song;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SongController extends Controller
{
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


        // Handle image upload
        $imagePath = $request->file('image')->store('songs', 'public');

        // Store song details in the database
        $song = Song::create([
            'name' => $request->name,
            'desc' => $request->desc,
            'image' => $imagePath,
            'album_id' => $request->albumId,
            'genre' => $request->genre,
            'duration' => $request->duration,
            'date' => $request->date,
            'user_id' => auth()->id(), // Associate song with the logged-in user
        ]);

        return response()->json([
            'message' => 'Song uploaded successfully!',
            'song' => [
                'id' => $song->id,
                'name' => $song->name,
                'desc' => $song->desc,
                'image' => asset('storage/' . $song->image), // Full URL
                'album_id' => $song->album_id,
                'genre' => $song->genre,
                'duration' => $song->duration,
                'date' => $song->date,
            ]
        ], 201);
    }

    // ✅ Get all songs
    public function index()
    {
        $songs = Song::where('user_id', auth()->id())->get();
        $songs->transform(function ($song) {
            $song->image = asset('storage/' . $song->image);
            return $song;
        });

        return response()->json($songs);
    }

    // ✅ Get a single song
    public function show($id)
    {
        $song = Song::where('id', $id)->where('user_id', auth()->id())->first();
        if (!$song) {
            return response()->json(['message' => 'Song not found'], 404);
        }
        $song->image = asset('storage/' . $song->image);

        return response()->json($song);
    }

    public function update(Request $request, $id)
    {
        try {
            // Find song or return 404
            $song = Song::where('id', $id)->where('user_id', auth()->id())->first();

            \Log::info("Updating song with ID: $id");

            if (!$song) {
                return response()->json(['error' => 'Song not found or unauthorized!'], 403);
            }

            // Validate input
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
                \Log::error('Validation failed', $validator->errors()->toArray());
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Update fields only if provided
            if ($request->has('name')) {
                \Log::info('Updating name');
                $song->name = $request->name;
            }
            if ($request->has('desc')) {
                \Log::info('Updating description');
                $song->desc = $request->desc;
            }
            if ($request->has('albumId')) {
                \Log::info('Updating album ID');
                $song->album_id = $request->albumId;
            }
            if ($request->has('genre')) {
                \Log::info('Updating genre');
                $song->genre = $request->genre;
            }
            if ($request->has('duration')) {
                \Log::info('Updating duration');
                $song->duration = $request->duration;
            }
            if ($request->has('date')) {
                \Log::info('Updating date');
                $song->date = $request->date;
            }

            // Handle image update
            if ($request->hasFile('image')) {
                \Log::info('Updating image');

                // Delete old image if exists
                if ($song->image) {
                    Storage::disk('public')->delete($song->image);
                }

                // Store new image
                $imagePath = $request->file('image')->store('songs', 'public');
                $song->image = $imagePath;
            }
            if ($request->has('albumId')) {
                $album = Album::where('id', $request->albumId)
                    ->where('user_id', auth()->id())
                    ->first();

                if (!$album) {
                    return response()->json(['error' => 'Album not found or unauthorized!'], 403);
                }

                $song->album_id = $request->albumId;
            }

            // Save changes
            $song->save();

            \Log::info("Song updated successfully: " . json_encode($song));

            return response()->json(['message' => 'Song updated successfully!', 'song' => $song], 200);
        } catch (\Exception $e) {
            \Log::error('Error updating song', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Song not found or update failed!'], 404);
        }
    }


    // ✅ Get songs by album ID
    public function getSongsByAlbum($albumId)
    {
        // Validate that the album exists
        $album = Album::where('id', $albumId)->where('user_id', auth()->id())->first();
        if (!$album) {
            return response()->json(['message' => 'Album not found'], 404);
        }

        // Fetch songs for the specified album
        $songs = Song::where('album_id', $albumId)->where('user_id', auth()->id())->get();

        $songs->transform(function ($song) {
            $song->image = asset('storage/' . $song->image);
            return $song;
        });

        return response()->json($songs);
    }


    // ✅ Delete a song
    public function destroy($id)
    {
        $song = Song::where('id', $id)->where('user_id', auth()->id())->first();
        if (!$song) {
            return response()->json(['message' => 'Song not found'], 404);
        }

        // Delete image
        if ($song->image) {
            Storage::disk('public')->delete($song->image);
        }

        $song->delete();
        return response()->json(['message' => 'Song deleted successfully!']);
    }

    // 🔵 GET LOGGED-IN USER'S SONGS
    public function getUserSongs()
    {
        $userId = auth()->id(); // Get logged-in user's ID

        $songs = Song::where('user_id', $userId)->get()->map(function ($song) {
            $song->image = url('storage/' . $song->image); // Convert image path to full URL
            return $song;
        });

        return response()->json($songs, 200);
    }

}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'desc', 'image', 'album_id', 'genre', 'duration', 'date', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}

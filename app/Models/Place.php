<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'category',
        'map_url',
        'open_hours',
        'photo'
    ];

    protected $casts = [
        'category' => 'integer',
    ];

    // IMPORTANT: Removed old accessor because photo is now a full URL
    // No more "../../assets/" prefix
}
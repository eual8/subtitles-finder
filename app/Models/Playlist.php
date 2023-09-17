<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $youtube_id
 * @property Carbon $synced_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'youtube_id',
    ];
}

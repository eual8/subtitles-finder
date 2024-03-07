<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class VideoService
{
    public function getвщVideosForSelect(int $playlistId): Collection
    {
        return Video::where('playlist_id', $playlistId)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($video) {
                $video->title = Str::replace(['«', '»', '"'], '', $video->title);

                return $video;
            })
            ->pluck('title', 'id');
    }
}

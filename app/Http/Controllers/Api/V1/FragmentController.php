<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\FragmentSearchRequest;
use App\Services\FragmentSearchService;

class FragmentController extends Controller
{
    public function search(FragmentSearchRequest $request, FragmentSearchService $searchService)
    {
        return $searchService->search(
            $request->validated('q'),
            $request->validated('playlistId'),
            $request->validated('videoId'),
            $request->validated('page', 1),
            $request->validated('perPage', 20)
        );
    }
}

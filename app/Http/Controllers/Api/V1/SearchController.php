<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SearchResultResource;
use App\Services\SearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    use ApiResponse;

    /**
     * Search activities and courses.
     *
     * @return AnonymousResourceCollection<SearchResultResource>
     */
    public function index(Request $request, SearchService $service): AnonymousResourceCollection
    {
        $q = $request->query('q', '');

        if (strlen($q) < 2) {
            return SearchResultResource::collection(collect([]))->additional(['success' => true, 'message' => null]);
        }

        $results = $service->search($q);

        return SearchResultResource::collection($results)->additional(['success' => true, 'message' => null]);
    }
}

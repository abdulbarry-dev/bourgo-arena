<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of active services.
     *
     * @return AnonymousResourceCollection<ServiceResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $services = Service::active()
            ->with(['plans', 'courses', 'events', 'activities'])
            ->withCount(['plans', 'courses', 'events', 'activities'])
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($services, ServiceResource::class);
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service): ServiceResource
    {
        abort_if(! $service->isActive(), 404, 'Service not found or inactive.');

        $service->load(['plans', 'courses', 'events', 'activities'])
            ->loadCount(['plans', 'courses', 'events', 'activities']);

        return new ServiceResource($service);
    }
}

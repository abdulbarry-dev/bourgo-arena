<?php

namespace App\Http\Responses\Api\V1;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

abstract class ApiBaseResponse implements Responsable
{
    public function __construct(
        protected mixed $data = null,
        protected ?string $message = null,
        protected int $status = 200
    ) {}

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $this->message,
            'data' => $this->data,
        ], $this->status);
    }
}

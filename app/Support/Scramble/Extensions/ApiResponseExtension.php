<?php

namespace App\Support\Scramble\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

class ApiResponseExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        foreach ($operation->responses as $response) {
            if (! $response instanceof Response) {
                continue;
            }

            if ($response->code >= 200 && $response->code < 300) {
                $this->wrapSuccessResponse($response);
            } elseif ($response->code >= 400) {
                $this->wrapErrorResponse($response);
            }
        }
    }

    private function wrapSuccessResponse(Response $response): void
    {
        foreach ($response->content as $mediaType => $schema) {
            $type = $schema instanceof Schema ? $schema->type : $schema;

            if ($type instanceof ObjectType && $type->hasProperty('success') && $type->hasProperty('data')) {
                continue;
            }

            $envelope = (new ObjectType)
                ->addProperty('success', (new BooleanType)->example(true))
                ->addProperty('message', (new StringType)->nullable(true)->example('Success message'))
                ->addProperty('data', $type);

            if ($schema instanceof Schema) {
                $schema->type = $envelope;
            } else {
                $response->setContent($mediaType, Schema::fromType($envelope));
            }
        }
    }

    private function wrapErrorResponse(Response $response): void
    {
        foreach ($response->content as $mediaType => $schema) {
            $type = $schema instanceof Schema ? $schema->type : $schema;

            if ($type instanceof ObjectType && $type->hasProperty('success') && $type->hasProperty('errors')) {
                continue;
            }

            $envelope = (new ObjectType)
                ->addProperty('success', (new BooleanType)->example(false))
                ->addProperty('message', (new StringType)->example('Error message'))
                ->addProperty('errors', $type);

            if ($schema instanceof Schema) {
                $schema->type = $envelope;
            } else {
                $response->setContent($mediaType, Schema::fromType($envelope));
            }
        }
    }
}
